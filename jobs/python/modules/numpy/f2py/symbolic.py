"""Fortran/C symbolic expressions

References:
- J3/21-007: Draft Fortran 202x. https://j3-fortran.org/doc/year/21/21-007.pdf

Copyright 1999 -- 2011 Pearu Peterson all rights reserved.
Copyright 2011 -- present NumPy Developers.
Permission to use, modify, and distribute this software is given under the
terms of the NumPy License.

NO WARRANTY IS EXPRESSED OR IMPLIED.  USE AT YOUR OWN RISK.
"""

# To analyze Fortran expressions to solve dimensions specifications,
# for instances, we implement a minimal symbolic engine for parsing
# expressions into a tree of expression instances. As a first
# instance, we care only about arithmetic expressions involving
# integers and operations like addition (+), subtraction (-),
# multiplication (*), division (Fortran / is Python //, Fortran // is
# concatenate), and exponentiation (**).  In addition, .pyf files may
# contain C expressions that support here is implemented as well.
#
# TODO: support logical constants (Op.BOOLEAN)
# TODO: support logical operators (.AND., ...)
# TODO: support defined operators (.MYOP., ...)
#
__all__ = ['Expr']


import re
import warnings
from enum import Enum
from math import gcd


class Language(Enum):
    """
    Used as Expr.tostring language argument.
    """
    Python = 0
    Fortran = 1
    C = 2


class Op(Enum):
    """
    Used as Expr op attribute.
    """
    INTEGER = 10
    REAL = 12
    COMPLEX = 15
    STRING = 20
    ARRAY = 30
    SYMBOL = 40
    TERNARY = 100
    APPLY = 200
    INDEXING = 210
    CONCAT = 220
    RELATIONAL = 300
    TERMS = 1000
    FACTORS = 2000
    REF = 3000
    DEREF = 3001


class RelOp(Enum):
    """
    Used in Op.RELATIONAL expression to specify the function part.
    """
    EQ = 1
    NE = 2
    LT = 3
    LE = 4
    GT = 5
    GE = 6

    @classmethod
    def fromstring(cls, s, language=Language.C):
        if language is Language.Fortran:
            return {'.eq.': RelOp.EQ, '.ne.': RelOp.NE,
                    '.lt.': RelOp.LT, '.le.': RelOp.LE,
                    '.gt.': RelOp.GT, '.ge.': RelOp.GE}[s.lower()]
        return {'==': RelOp.EQ, '!=': RelOp.NE, '<': RelOp.LT,
                '<=': RelOp.LE, '>': RelOp.GT, '>=': RelOp.GE}[s]

    def tostring(self, language=Language.C):
        if language is Language.Fortran:
            return {RelOp.EQ: '.eq.', RelOp.NE: '.ne.',
                    RelOp.LT: '.lt.', RelOp.LE: '.le.',
                    RelOp.GT: '.gt.', RelOp.GE: '.ge.'}[self]
        return {RelOp.EQ: '==', RelOp.NE: '!=',
                RelOp.LT: '<', RelOp.LE: '<=',
                RelOp.GT: '>', RelOp.GE: '>='}[self]


class ArithOp(Enum):
    """
    Used in Op.APPLY expression to specify the function part.
    """
    POS = 1
    NEG = 2
    ADD = 3
    SUB = 4
    MUL = 5
    DIV = 6
    POW = 7


class OpError(Exception):
    pass


class Precedence(Enum):
    """
    Used as Expr.tostring precedence argument.
    """
    ATOM = 0
    POWER = 1
    UNARY = 2
    PRODUCT = 3
    SUM = 4
    LT = 6
    EQ = 7
    LAND = 11
    LOR = 12
    TERNARY = 13
    ASSIGN = 14
    TUPLE = 15
    NONE = 100


integer_types = (int,)
number_types = (int, float)


def _pairs_add(d, k, v):
    # Internal utility method for updating terms and factors data.
    c = d.get(k)
    if c is None:
        d[k] = v
    else:
        c = c + v
        if c:
            d[k] = c
        else:
            del d[k]


class ExprWarning(UserWarning):
    pass


def ewarn(message):
    warnings.warn(message, ExprWarning, stacklevel=2)


class Expr:
    """Represents a Fortran expression as a op-data pair.

    Expr instances are hashable and sortable.
    """

    @staticmethod
    def parse(s, language=Language.C):
        """Parse a Fortran expression to a Expr.
        """
        return fromstring(s, language=language)

    def __init__(self, op, data):
        assert isinstance(op, Op)

        # sanity checks
        if op is Op.INTEGER:
            # data is a 2-tuple of numeric object and a kind value
            # (default is 4)
            assert isinstance(data, tuple) and len(data) == 2
            assert isinstance(data[0], int)
            assert isinstance(data[1], (int, str)), data
        elif op is Op.REAL:
            # data is a 2-tuple of numeric object and a kind value
            # (default is 4)
            assert isinstance(data, tuple) and len(data) == 2
            assert isinstance(data[0], float)
            assert isinstance(data[1], (int, str)), data
        elif op is Op.COMPLEX:
            # data is a 2-tuple of constant expressions
            assert isinstance(data, tuple) and len(data) == 2
        elif op is Op.STRING:
            # data is a 2-tuple of quoted string and a kind value
            # (default is 1)
            assert isinstance(data, tuple) and len(data) == 2
            assert (isinstance(data[0], str)
                    and data[0][::len(data[0])-1] in ('""', "''", '@@'))
            assert isinstance(data[1], (int, str)), data
        elif op is Op.SYMBOL:
            # data is any hashable object
            assert hash(data) is not None
        elif op in (Op.ARRAY, Op.CONCAT):
            # data is a tuple of expressions
            assert isinstance(data, tuple)
            assert all(isinstance(item, Expr) for item in data), data
        elif op in (Op.TERMS, Op.FACTORS):
            # data is {<term|base>:<coeff|exponent>} where dict values
            # are nonzero Python integers
            assert isinstance(data, dict)
        elif op is Op.APPLY:
            # data is (<function>, <operands>, <kwoperands>) where
            # operands are Expr instances
            assert isinstance(data, tuple) and len(data) == 3
            # function is any hashable object
            assert hash(data[0]) is not None
            assert isinstance(data[1], tuple)
            assert isinstance(data[2], dict)
        elif op is Op.INDEXING:
            # data is (<object>, <indices>)
            assert isinstance(data, tuple) and len(data) == 2
            # function is any hashable object
            assert hash(data[0]) is not None
        elif op is Op.TERNARY:
            # data is (<cond>, <expr1>, <expr2>)
            assert isinstance(data, tuple) and len(data) == 3
        elif op in (Op.REF, Op.DEREF):
            # data is Expr instance
            assert isinstance(data, Expr)
        elif op is Op.RELATIONAL:
            # data is (<relop>, <left>, <right>)
            assert isinstance(data, tuple) and len(data) == 3
        else:
            raise NotImplementedError(
                f'unknown op or missing sanity check: {op}')

        self.op = op
        self.data = data

    def __eq__(self, other):
        return (isinstance(other, Expr)
                and self.op is other.op
                and self.data == other.data)

    def __hash__(self):
        if self.op in (Op.TERMS, Op.FACTORS):
            data = tuple(sorted(self.data.items()))
        elif self.op is Op.APPLY:
            data = self.data[:2] + tuple(sorted(self.data[2].items()))
        else:
            data = self.data
        return hash((self.op, data))

    def __lt__(self, other):
        if isinstance(other, Expr):
            if self.op is not other.op:
                return self.op.value < other.op.value
            if self.op in (Op.TERMS, Op.FACTORS):
                return (tuple(sorted(self.data.items()))
                        < tuple(sorted(other.data.items())))
            if self.op is Op.APPLY:
                if self.data[:2] != other.data[:2]:
                    return self.data[:2] < other.data[:2]
                return tuple(sorted(self.data[2].items())) < tuple(
                    sorted(other.data[2].items()))
            return self.data < other.data
        return NotImplemented

    def __le__(self, other): return self == other or self < other

    def __gt__(self, other): return not (self <= other)

    def __ge__(self, other): return not (self < other)

    def __repr__(self):
        return f'{type(self).__name__}({self.op}, {self.data!r})'

    def __str__(self):
        return self.tostring()

    def tostring(self, parent_precedence=Precedence.NONE,
                 language=Language.Fortran):
        """Return a string representation of Expr.
        """
        if self.op in (Op.INTEGER, Op.REAL):
            precedence = (Precedence.SUM if self.data[0] < 0
                          else Precedence.ATOM)
            r = str(self.data[0]) + (f'_{self.data[1]}'
                                     if self.data[1] != 4 else '')
        elif self.op is Op.COMPLEX:
            r = ', '.join(item.tostring(Precedence.TUPLE, language=language)
                          for item in self.data)
            r = '(' + r + ')'
            precedence = Precedence.ATOM
        elif self.op is Op.SYMBOL:
            precedence = Precedence.ATOM
            r = str(self.data)
        elif self.op is Op.STRING:
            r = self.data[0]
            if self.data[1] != 1:
                r = self.data[1] + '_' + r
            precedence = Precedence.ATOM
        elif self.op is Op.ARRAY:
            r = ', '.join(item.tostring(Precedence.TUPLE, language=language)
                          for item in self.data)
            r = '[' + r + ']'
            precedence = Precedence.ATOM
        elif self.op is Op.TERMS:
            terms = []
            for term, coeff in sorted(self.data.items()):
                if coeff < 0:
                    op = ' - '
                    coeff = -coeff
                else:
                    op = ' + '
                if coeff == 1:
                    term = term.tostring(Precedence.SUM, language=language)
                else:
                    if term == as_number(1):
                        term = str(coeff)
                    else:
                        term = f'{coeff} * ' + term.tostring(
                            Precedence.PRODUCT, language=language)
                if terms:
                    terms.append(op)
                elif op == ' - ':
                    terms.append('-')
                terms.append(term)
            r = ''.join(terms) or '0'
            precedence = Precedence.SUM if terms else Precedence.ATOM
        elif self.op is Op.FACTORS:
            factors = []
            tail = []
            for base, exp in sorted(self.data.items()):
                op = ' * '
                if exp == 1:
                    factor = base.tostring(Precedence.PRODUCT,
                                           language=language)
                elif language is Language.C:
                    if exp in range(2, 10):
                        factor = base.tostring(Precedence.PRODUCT,
                                               language=language)
                        factor = ' * '.join([factor] * exp)
                    elif exp in range(-10, 0):
                        factor = base.tostring(Precedence.PRODUCT,
                                               language=language)
                        tail += [factor] * -exp
                        continue
                    else:
                        factor = base.tostring(Precedence.TUPLE,
                                               language=language)
                        factor = f'pow({factor}, {exp})'
                else:
                    factor = base.tostring(Precedence.POWER,
                                           language=language) + f' ** {exp}'
                if factors:
                    factors.append(op)
                factors.append(factor)
            if tail:
                if not factors:
                    factors += ['1']
                factors += ['/', '(', ' * '.join(tail), ')']
            r = ''.join(factors) or '1'
            precedence = Precedence.PRODUCT if factors else Precedence.ATOM
        elif self.op is Op.APPLY:
            name, args, kwargs = self.data
            if name is ArithOp.DIV and language is Language.C:
                numer, denom = [arg.tostring(Precedence.PRODUCT,
                                             language=language)
                                for arg in args]
                r = f'{numer} / {denom}'
                precedence = Precedence.PRODUCT
            else:
                args = [arg.tostring(Precedence.TUPLE, language=language)
                        for arg in args]
                args += [k + '=' + v.tostring(Precedence.NONE)
                         for k, v in kwargs.items()]
                r = f'{name}({", ".join(args)})'
                precedence = Precedence.ATOM
        elif self.op is Op.INDEXING:
            name = self.data[0]
            args = [arg.tostring(Precedence.TUPLE, language=language)
                    for arg in self.data[1:]]
            r = f'{name}[{", ".join(args)}]'
            precedence = Precedence.ATOM
        elif self.op is Op.CONCAT:
            args = [arg.tostring(Precedence.PRODUCT, language=language)
                    for arg in self.data]
            r = " // ".join(args)
            precedence = Precedence.PRODUCT
        elif self.op is Op.TERNARY:
            cond, expr1, expr2 = [a.tostring(Precedence.TUPLE,
                                             language=language)
                                  for a in self.data]
            if language is Language.C:
                r = f'({cond}?{expr1}:{expr2})'
            elif language is Language.Python:
                r = f'({expr1} if {cond} else {expr2})'
            elif language is Language.Fortran:
                r = f'merge({expr1}, {expr2}, {cond})'
            else:
                raise NotImplementedError(
                    f'tostring for {self.op} and {language}')
            precedence = Precedence.ATOM
        elif self.op is Op.REF:
            r = '&' + self.data.tostring(Precedence.UNARY, language=language)
            precedence = Precedence.UNARY
        elif self.op is Op.DEREF:
            r = '*' + self.data.tostring(Precedence.UNARY, language=language)
            precedence = Precedence.UNARY
        elif self.op is Op.RELATIONAL:
            rop, left, right = self.data
            precedence = (Precedence.EQ if rop in (RelOp.EQ, RelOp.NE)
                          else Precedence.LT)
            left = left.tostring(precedence, language=language)
            right = right.tostring(precedence, language=language)
            rop = rop.tostring(language=language)
            r = f'{left} {rop} {right}'
        else:
            raise NotImplementedError(f'tostring for op {self.op}')
        if parent_precedence.value < precedence.value:
            # If parent precedence is higher than operand precedence,
            # operand will be enclosed in parenthesis.
            return '(' + r + ')'
        return r

    def __pos__(self):
        return self

    def __neg__(self):
        return self * -1

    def __add__(self, other):
        other = as_expr(other)
        if isinstance(other, Expr):
            if self.op is other.op:
                if self.op in (Op.INTEGER, Op.REAL):
                    return as_number(
                        self.data[0] + other.data[0],
                        max(self.data[1], other.data[1]))
                if self.op is Op.COMPLEX:
                    r1, i1 = self.data
                    r2, i2 = other.data
                    return as_complex(r1 + r2, i1 + i2)
                if self.op is Op.TERMS:
                    r = Expr(self.op, dict(self.data))
                    for k, v in other.data.items():
                        _pairs_add(r.data, k, v)
                    return normalize(r)
            if self.op is Op.COMPLEX and other.op in (Op.INTEGER, Op.REAL):
                return self + as_complex(other)
            elif self.op in (Op.INTEGER, Op.REAL) and other.op is Op.COMPLEX:
                return as_complex(self) + other
            elif self.op is Op.REAL 
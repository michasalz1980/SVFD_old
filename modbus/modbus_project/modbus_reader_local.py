#!/usr/bin/env python3
"""
Abwasser Modbus Reader - Version mit lokalen Modulen
Liest Modbus Register aus und speichert Werte in MySQL Datenbank
Optimiert für CronJob Ausführung alle 5 Minuten

LOKALE MODULE VERSION:
- Nutzt Module aus ./modules/ Verzeichnis
- Keine Installation von pip packages nötig
- Ideal für Shared Hosting

Author: System
Version: 1.1 (Local Modules)
"""

import sys
import os

# WICHTIG: Lokale Module zum Python Path hinzufügen
script_dir = os.path.dirname(os.path.abspath(__file__))
modules_dir = os.path.join(script_dir, 'modules')

# Prüfen ob modules Verzeichnis existiert
if os.path.exists(modules_dir):
    # Module Verzeichnis an den Anfang des Python Path setzen
    sys.path.insert(0, modules_dir)
    print(f"Using local modules from: {modules_dir}")
else:
    print(f"Warning: Local modules directory not found at {modules_dir}")
    print("Falling back to system modules...")

# Jetzt die normalen Imports
import logging
import configparser
import traceback
import signal
import time
from datetime import datetime, timedelta
from typing import Dict, Optional, Any
import json

# Versuche lokale Module zu importieren, dann system modules
try:
    import pymysql
    print("✓ PyMySQL loaded")
except ImportError as e:
    print(f"✗ Failed to import PyMySQL: {e}")
    sys.exit(1)

try:
    from pymodbus.client import ModbusTcpClient
    from pymodbus.exceptions import ModbusException, ConnectionException
    print("✓ Pymodbus loaded")
except ImportError as e:
    print(f"✗ Failed to import Pymodbus: {e}")
    sys.exit(1)

# Logging Handler
from logging.handlers import RotatingFileHandler

class TimeoutException(Exception):
    """Custom Timeout Exception"""
    pass

def timeout_handler(signum, frame):
    raise TimeoutException("Script execution timeout")

class ModbusReader:
    def __init__(self, config_file: str = 'config.ini'):
        self.config = configparser.ConfigParser()
        self.config.read(config_file)
        self.logger = None
        self.db_connection = None
        self.modbus_client = None
        self.script_dir = os.path.dirname(os.path.abspath(__file__))
        self.setup_logging()
        
    def setup_logging(self):
        """Setup logging konfiguration"""
        log_level = getattr(logging, self.config.get('LOGGING', 'log_level', fallback='INFO'))
        log_file = self.config.get('LOGGING', 'log_file', fallback='logs/modbus_reader.log')
        
        # Relativen Pfad zu absolutem Pfad machen
        if not os.path.isabs(log_file):
            log_file = os.path.join(self.script_dir, log_file)
        
        # Log-Verzeichnis erstellen falls nicht vorhanden
        os.makedirs(os.path.dirname(log_file), exist_ok=True)
        
        max_size = self.config.get('LOGGING', 'max_file_size', fallback='10MB')
        backup_count = self.config.getint('LOGGING', 'backup_count', fallback=5)
        
        # Convert size string to bytes
        size_value = int(max_size.replace('MB', '')) * 1024 * 1024
        
        # Create logger
        self.logger = logging.getLogger('ModbusReader')
        self.logger.setLevel(log_level)
        
        # Avoid duplicate handlers
        if self.logger.handlers:
            self.logger.handlers.clear()
        
        # Create formatters
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
        
        # File handler with rotation
        try:
            file_handler = RotatingFileHandler(
                log_file, maxBytes=size_value, backupCount=backup_count
            )
            file_handler.setFormatter(formatter)
            self.logger.addHandler(file_handler)
        except Exception as e:
            print(f"Warning: Could not setup file logging: {e}")
        
        # Console handler für CronJob
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        self.logger.addHandler(console_handler)
        
        self.logger.info("Logging setup completed")
        self.logger.info(f"Script directory: {self.script_dir}")
        self.logger.info(f"Using modules from: {modules_dir if os.path.exists(modules_dir) else 'system'}")
    
    def log_to_database(self, level: str, message: str, details: Dict = None):
        """Log message to database"""
        if not self.config.getboolean('LOGGING', 'log_to_database', fallback=True):
            return
            
        try:
            if self.db_connection:
                with self.db_connection.cursor() as cursor:
                    sql = """INSERT INTO system_logs (log_level, message, details, script_name) 
                            VALUES (%s, %s, %s, %s)"""
                    cursor.execute(sql, (level, message, json.dumps(details) if details else None, 'modbus_reader_local'))
                self.db_connection.commit()
        except Exception as e:
            self.logger.error(f"Failed to log to database: {e}")
    
    def connect_database(self) -> bool:
        """Establish database connection"""
        try:
            self.db_connection = pymysql.connect(
                host=self.config.get('DATABASE', 'host'),
                user=self.config.get('DATABASE', 'user'),
                password=self.config.get('DATABASE', 'password'),
                database=self.config.get('DATABASE', 'database'),
                charset=self.config.get('DATABASE', 'charset', fallback='utf8mb4'),
                autocommit=False,
                connect_timeout=self.config.getint('DATABASE', 'pool_timeout', fallback=30)
            )
            self.logger.info("Database connection established")
            self.log_to_database('INFO', 'Database connection established')
            return True
        except Exception as e:
            self.logger.error(f"Database connection failed: {e}")
            self.log_to_database('ERROR', f'Database connection failed: {str(e)}')
            return False
    
    def connect_modbus(self) -> bool:
        """Establish Modbus connection"""
        try:
            server_ip = self.config.get('MODBUS', 'server_ip')
            server_port = self.config.getint('MODBUS', 'server_port')
            timeout = self.config.getint('MODBUS', 'timeout', fallback=10)
            
            self.modbus_client = ModbusTcpClient(
                host=server_ip,
                port=server_port,
                timeout=timeout
            )
            
            if self.modbus_client.connect():
                self.logger.info(f"Modbus connection established to {server_ip}:{server_port}")
                self.log_to_database('INFO', f'Modbus connection established to {server_ip}:{server_port}')
                return True
            else:
                self.logger.error(f"Failed to connect to Modbus server {server_ip}:{server_port}")
                self.log_to_database('ERROR', f'Failed to connect to Modbus server {server_ip}:{server_port}')
                return False
        except Exception as e:
            self.logger.error(f"Modbus connection error: {e}")
            self.log_to_database('ERROR', f'Modbus connection error: {str(e)}')
            return False
    
    def read_modbus_register(self, address: int, count: int = 2) -> Optional[float]:
        """Read float value from Modbus register (2 registers for float)"""
        try:
            unit_id = self.config.getint('MODBUS', 'unit_id', fallback=1)
            
            # For M-Bus Gateway: Direct register addresses (no 40000 offset needed)
            # Only subtract 40001 if address is in 40000+ range (traditional Modbus addressing)
            if address > 40000:
                modbus_address = address - 40001
            else:
                # Direct register address for M-Bus Gateway
                modbus_address = address
            
            result = self.modbus_client.read_holding_registers(
                address=modbus_address,
                count=count,
                slave=unit_id
            )
            
            if result.isError():
                self.logger.warning(f"Modbus read error for register {address}: {result}")
                return None
            
            # Convert registers to float (assuming IEEE 754 float format)
            if len(result.registers) >= 2:
                # Combine two 16-bit registers to form a 32-bit float
                combined = (result.registers[0] << 16) | result.registers[1]
                
                # Convert to float (this might need adjustment based on your device's format)
                import struct
                float_value = struct.unpack('>f', struct.pack('>I', combined))[0]
                return float_value
            else:
                self.logger.warning(f"Insufficient registers returned for address {address}")
                return None
                
        except ModbusException as e:
            self.logger.error(f"Modbus exception reading register {address}: {e}")
            return None
        except Exception as e:
            self.logger.error(f"Unexpected error reading register {address}: {e}")
            return None
    
    def get_register_config(self) -> Dict[str, Dict]:
        """Load register configuration from database"""
        config = {}
        try:
            with self.db_connection.cursor(pymysql.cursors.DictCursor) as cursor:
                sql = """SELECT register_name, register_address, data_type, scale_factor, unit 
                        FROM modbus_register_config WHERE is_active = 1"""
                cursor.execute(sql)
                results = cursor.fetchall()
                
                for row in results:
                    config[row['register_name']] = {
                        'address': row['register_address'],
                        'data_type': row['data_type'],
                        'scale_factor': float(row['scale_factor']),
                        'unit': row['unit']
                    }
            return config
        except Exception as e:
            self.logger.error(f"Failed to load register config: {e}")
            # Fallback to hardcoded config for M-Bus Gateway
            return {
                'durchflussmenge': {'address': 210, 'data_type': 'FLOAT', 'scale_factor': 1.0, 'unit': 'm³/h'},
                'wasserstand': {'address': 220, 'data_type': 'FLOAT', 'scale_factor': 1.0, 'unit': 'm'},
                'totalizer': {'address': 230, 'data_type': 'FLOAT', 'scale_factor': 1.0, 'unit': 'm³'},
                'sensor_strom': {'address': 240, 'data_type': 'FLOAT', 'scale_factor': 1.0, 'unit': 'mA'}
            }
    
    def read_all_values(self) -> Dict[str, Any]:
        """Read all configured values from Modbus"""
        values = {}
        register_config = self.get_register_config()
        
        for register_name, config in register_config.items():
            raw_value = self.read_modbus_register(config['address'])
            if raw_value is not None:
                # Apply scale factor
                scaled_value = raw_value * config['scale_factor']
                values[register_name] = scaled_value
                self.logger.debug(f"{register_name}: {scaled_value} {config['unit']}")
            else:
                values[register_name] = None
                self.logger.warning(f"Failed to read {register_name}")
        
        return values
    
    def validate_values(self, values: Dict[str, Any]) -> bool:
        """Validate measured values against thresholds"""
        try:
            if values.get('durchflussmenge') is not None:
                max_durchfluss = self.config.getfloat('MONITORING', 'max_durchfluss', fallback=100.0)
                if values['durchflussmenge'] > max_durchfluss:
                    self.logger.warning(f"Durchfluss over threshold: {values['durchflussmenge']} > {max_durchfluss}")
            
            if values.get('wasserstand') is not None:
                max_wasserstand = self.config.getfloat('MONITORING', 'max_wasserstand', fallback=5.0)
                if values['wasserstand'] > max_wasserstand:
                    self.logger.warning(f"Wasserstand over threshold: {values['wasserstand']} > {max_wasserstand}")
            
            if values.get('sensor_strom') is not None:
                min_strom = self.config.getfloat('MONITORING', 'min_sensor_strom', fallback=4.0)
                max_strom = self.config.getfloat('MONITORING', 'max_sensor_strom', fallback=20.0)
                if not min_strom <= values['sensor_strom'] <= max_strom:
                    self.logger.warning(f"Sensor current out of range: {values['sensor_strom']} not in [{min_strom}, {max_strom}]")
            
            return True
        except Exception as e:
            self.logger.error(f"Validation error: {e}")
            return False
    
    def save_to_database(self, values: Dict[str, Any], status: str = 'OK', error_msg: str = None) -> bool:
        """Save values to database"""
        retries = self.config.getint('SYSTEM', 'db_retry_attempts', fallback=3)
        retry_delay = self.config.getint('SYSTEM', 'retry_delay', fallback=2)
        
        for attempt in range(retries):
            try:
                with self.db_connection.cursor() as cursor:
                    sql = """INSERT INTO abwasser_messwerte 
                            (durchflussmenge, wasserstand, totalizer, sensor_strom, modbus_status, error_message)
                            VALUES (%s, %s, %s, %s, %s, %s)"""
                    
                    cursor.execute(sql, (
                        values.get('durchflussmenge'),
                        values.get('wasserstand'),
                        values.get('totalizer'),
                        values.get('sensor_strom'),
                        status,
                        error_msg
                    ))
                
                self.db_connection.commit()
                self.logger.info(f"Data saved to database: {values}")
                return True
                
            except Exception as e:
                self.logger.error(f"Database save attempt {attempt + 1} failed: {e}")
                if attempt < retries - 1:
                    time.sleep(retry_delay)
                else:
                    self.log_to_database('ERROR', f'Failed to save measurement data after {retries} attempts', {'error': str(e), 'values': values})
        
        return False
    
    def cleanup_old_data(self):
        """Clean up old data if configured"""
        try:
            cleanup_days = self.config.getint('MAINTENANCE', 'cleanup_old_data_days', fallback=90)
            cleanup_hour = self.config.getint('MAINTENANCE', 'cleanup_hour', fallback=2)
            
            current_hour = datetime.now().hour
            if current_hour == cleanup_hour:
                with self.db_connection.cursor() as cursor:
                    # Delete old measurement data
                    sql_measurements = """DELETE FROM abwasser_messwerte 
                                        WHERE timestamp < DATE_SUB(NOW(), INTERVAL %s DAY)"""
                    cursor.execute(sql_measurements, (cleanup_days,))
                    
                    # Delete old logs
                    sql_logs = """DELETE FROM system_logs 
                                WHERE timestamp < DATE_SUB(NOW(), INTERVAL %s DAY)"""
                    cursor.execute(sql_logs, (cleanup_days,))
                    
                self.db_connection.commit()
                self.logger.info(f"Cleanup completed: removed data older than {cleanup_days} days")
                self.log_to_database('INFO', f'Cleanup completed: removed data older than {cleanup_days} days')
        except Exception as e:
            self.logger.error(f"Cleanup failed: {e}")
    
    def run(self) -> int:
        """Main execution method"""
        try:
            # Setup timeout for script execution
            timeout = self.config.getint('SYSTEM', 'execution_timeout', fallback=120)
            signal.signal(signal.SIGALRM, timeout_handler)
            signal.alarm(timeout)
            
            self.logger.info("Starting Modbus reader execution (Local Modules Version)")
            
            # Connect to database
            if not self.connect_database():
                return 1
            
            # Connect to Modbus
            if not self.connect_modbus():
                self.save_to_database({}, 'CONNECTION_FAILED', 'Failed to connect to Modbus server')
                return 2
            
            # Read values
            values = self.read_all_values()
            
            # Validate values
            self.validate_values(values)
            
            # Check if any values were read successfully
            if any(v is not None for v in values.values()):
                # Save to database
                if self.save_to_database(values):
                    self.logger.info("Execution completed successfully")
                    self.log_to_database('INFO', 'Modbus read cycle completed successfully', values)
                else:
                    return 3
            else:
                # No values could be read
                self.save_to_database(values, 'ERROR', 'No values could be read from Modbus')
                self.logger.error("No values could be read from Modbus")
                return 4
            
            # Perform cleanup if needed
            self.cleanup_old_data()
            
            return 0
            
        except TimeoutException:
            self.logger.error(f"Script execution timeout after {timeout} seconds")
            self.log_to_database('ERROR', f'Script execution timeout after {timeout} seconds')
            return 5
        except Exception as e:
            self.logger.error(f"Unexpected error: {e}")
            self.logger.error(f"Traceback: {traceback.format_exc()}")
            self.log_to_database('ERROR', f'Unexpected error: {str(e)}', {'traceback': traceback.format_exc()})
            return 6
        finally:
            # Cleanup connections
            signal.alarm(0)  # Disable timeout
            if self.modbus_client:
                self.modbus_client.close()
            if self.db_connection:
                self.db_connection.close()

def main():
    """Main entry point"""
    config_file = sys.argv[1] if len(sys.argv) > 1 else 'config.ini'
    
    if not os.path.exists(config_file):
        print(f"Error: Configuration file '{config_file}' not found")
        sys.exit(1)
    
    reader = ModbusReader(config_file)
    exit_code = reader.run()
    sys.exit(exit_code)

if __name__ == "__main__":
    main()
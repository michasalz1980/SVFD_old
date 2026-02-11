<?php
session_start();
$config = include('config.php');
$title = "Login";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === $config['username'] && $password === $config['password']) {
        $_SESSION['loggedin'] = true;
        header('Location: protected.php');
        exit;
    } else {
        $error = 'Ungültige Anmeldeinformationen';
    }
}
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="text-center mt-5">Login</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

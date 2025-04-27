<?php
require "functions.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="style.css">
  <title>Login To Website Kasie</title>
</head>
<body>
    <section>
        <div class="form-box">
            <div class="form-value">
                <form method="post">
                    <h2>Login</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input class="form-control" id="inputtext" name="username" type="text" required />
                        <label for="inputtext">Username</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input class="form-control" id="inputPassword" name="password" type="password" required />
                        <label for="inputPassword">Password</label>
                    </div>
                    <div class="forget">
                        <label for="inputRememberPassword"><input type="checkbox" id="inputRememberPassword">Remember Me  <a href="password.html" class="forgot-pass">Forget Password</a></label>
                    </div>
                    <button type="submit" name="login" class="btn btn-pill text-white btn-block btn-primary">Log in</button>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
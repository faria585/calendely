<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name']);
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            $error = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed_password')";
            
            if ($conn->query($insert_query) === TRUE) {
                $success = "Registration successful! You can now login.";
                
                // Create default availability for new user
                $user_id = $conn->insert_id;
                
                // Monday to Friday, 9 AM to 5 PM
                for ($day = 1; $day <= 5; $day++) {
                    $insert_availability = "INSERT INTO availability (user_id, day_of_week, start_time, end_time, is_available) 
                                           VALUES ('$user_id', '$day', '09:00:00', '17:00:00', 1)";
                    $conn->query($insert_availability);
                }
                
                // Redirect to login page after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Calendly Clone</title>
    <style>
        :root {
            --primary-color: #0069ff;
            --primary-dark: #0053cc;
            --secondary-color: #00a2ff;
            --text-color: #4f5e71;
            --dark-text: #1a1a1a;
            --light-text: #6e7582;
            --light-bg: #f8f8fa;
            --white: #ffffff;
            --success: #00c389;
            --error: #ff4d4f;
            --border-color: #e1e1e1;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 450px;
            width: 100%;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 40px;
            margin: 20px 0;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo a {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        h1 {
            font-size: 24px;
            color: var(--dark-text);
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-text);
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            color: var(--dark-text);
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn {
            width: 100%;
            padding: 12px 15px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            color: var(--error);
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .success-message {
            color: var(--success);
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 12px;
            color: var(--light-text);
            margin-top: 5px;
        }

        @media (max-width: 500px) {
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <a href="index.php">Calendly</a>
        </div>
        <h1>Create Your Account</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">Password must be at least 6 characters long</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

    <script>
        // Redirect to login page after successful signup
        <?php if (!empty($success)): ?>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>

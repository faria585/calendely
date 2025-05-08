<?php
require_once 'db.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);

// Get user's availability
$availability_query = "SELECT * FROM availability WHERE user_id = '$user_id'";
$availability_result = $conn->query($availability_query);
$availability = [];

if ($availability_result && $availability_result->num_rows > 0) {
    while ($row = $availability_result->fetch_assoc()) {
        $availability[$row['day_of_week']] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'is_available' => $row['is_available']
        ];
    }
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete existing availability
    $delete_query = "DELETE FROM availability WHERE user_id = '$user_id'";
    $conn->query($delete_query);
    
    // Insert new availability
    for ($day = 0; $day < 7; $day++) {
        $is_available = isset($_POST['available'][$day]) ? 1 : 0;
        
        if ($is_available) {
            $start_time = $_POST['start_time'][$day];
            $end_time = $_POST['end_time'][$day];
            
            $insert_query = "INSERT INTO availability (user_id, day_of_week, start_time, end_time, is_available) 
                            VALUES ('$user_id', '$day', '$start_time', '$end_time', '$is_available')";
            
            if (!$conn->query($insert_query)) {
                $error_message = "Error updating availability: " . $conn->error;
                break;
            }
        }
    }
    
    if (empty($error_message)) {
        $success_message = "Availability updated successfully!";
    }
}

// Day names
$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Time slots for dropdown
$time_slots = [];
for ($hour = 0; $hour < 24; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 30) {
        $time = sprintf("%02d:%02d:00", $hour, $minute);
        $display_time = date("g:i A", strtotime($time));
        $time_slots[$time] = $display_time;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Availability - Calendly Clone</title>
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
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--white);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .menu-item:hover {
            background-color: rgba(0, 105, 255, 0.05);
            color: var(--primary-color);
        }

        .menu-item.active {
            background-color: rgba(0, 105, 255, 0.1);
            color: var(--primary-color);
            font-weight: 500;
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 24px;
            color: var(--dark-text);
        }

        .user-menu {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .user-name {
            font-weight: 500;
            color: var(--dark-text);
        }

        .dropdown-icon {
            margin-left: 5px;
        }

        .card {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline:hover {
            background-color: rgba(0, 105, 255, 0.05);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-text);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            color: var(--dark-text);
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-text {
            font-size: 14px;
            color: var(--light-text);
            margin-top: 5px;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(0, 195, 137, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background-color: rgba(255, 77, 79, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .availability-form {
            margin-top: 20px;
        }

        .day-row {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .day-row:last-child {
            border-bottom: none;
        }

        .day-checkbox {
            margin-right: 15px;
        }

        .day-name {
            width: 100px;
            font-weight: 500;
            color: var(--dark-text);
        }

        .time-selects {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .time-select {
            width: 120px;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            color: var(--dark-text);
        }

        .time-separator {
            margin: 0 10px;
            color: var(--light-text);
        }

        .form-actions {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .day-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .time-selects {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">Calendly</a>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i>📊</i> Dashboard
                </a>
                <a href="create_schedule.php" class="menu-item active">
                    <i>📅</i> Availability
                </a>
                <a href="#" class="menu-item">
                    <i>🔗</i> Meeting Types
                </a>
                <a href="#" class="menu-item">
                    <i>📝</i> Scheduled Events
                </a>
                <a href="#" class="menu-item">
                    <i>⚙️</i> Settings
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1 class="page-title">Set Your Availability</h1>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo substr($user_data['name'], 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo $user_data['name']; ?></span>
                    <span class="dropdown-icon">▼</span>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Weekly Hours</h2>
                </div>
                <div class="card-body">
                    <p>Set your weekly availability to let people know when they can schedule meetings with you.</p>
                    
                    <form action="create_schedule.php" method="POST" class="availability-form">
                        <?php for ($day = 0; $day < 7; $day++): ?>
                            <?php 
                                $is_available = isset($availability[$day]) && $availability[$day]['is_available'] == 1;
                                $start_time = isset($availability[$day]) ? $availability[$day]['start_time'] : '09:00:00';
                                $end_time = isset($availability[$day]) ? $availability[$day]['end_time'] : '17:00:00';
                            ?>
                            <div class="day-row">
                                <div class="day-checkbox">
                                    <input type="checkbox" id="day_<?php echo $day; ?>" name="available[<?php echo $day; ?>]" <?php echo $is_available ? 'checked' : ''; ?> onchange="toggleTimeSelects(<?php echo $day; ?>)">
                                </div>
                                <label for="day_<?php echo $day; ?>" class="day-name"><?php echo $day_names[$day]; ?></label>
                                <div class="time-selects" id="time_selects_<?php echo $day; ?>" <?php echo !$is_available ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                                    <select name="start_time[<?php echo $day; ?>]" class="time-select">
                                        <?php foreach ($time_slots as $time => $display): ?>
                                            <option value="<?php echo $time; ?>" <?php echo $time == $start_time ? 'selected' : ''; ?>><?php echo $display; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="time-separator">to</span>
                                    <select name="end_time[<?php echo $day; ?>]" class="time-select">
                                        <?php foreach ($time_slots as $time => $display): ?>
                                            <option value="<?php echo $time; ?>" <?php echo $time == $end_time ? 'selected' : ''; ?>><?php echo $display; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <div class="form-actions">
                            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTimeSelects(day) {
            const checkbox = document.getElementById(`day_${day}`);
            const timeSelects = document.getElementById(`time_selects_${day}`);
            
            if (checkbox.checked) {
                timeSelects.style.opacity = '1';
                timeSelects.style.pointerEvents = 'auto';
            } else {
                timeSelects.style.opacity = '0.5';
                timeSelects.style.pointerEvents = 'none';
            }
        }
    </script>
</body>
</html>

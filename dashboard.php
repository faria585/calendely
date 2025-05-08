<?php
require_once 'db.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);
$upcoming_appointments = getUserAppointments($conn, $user_id);

// Get user's availability
$availability = getUserAvailability($conn, $user_id);

// Format availability for display
$availability_by_day = [];
foreach ($availability as $slot) {
    $day = $slot['day_of_week'];
    if (!isset($availability_by_day[$day])) {
        $availability_by_day[$day] = [];
    }
    $availability_by_day[$day][] = [
        'start' => date('g:i A', strtotime($slot['start_time'])),
        'end' => date('g:i A', strtotime($slot['end_time'])),
    ];
}

// Get day names
$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Count upcoming appointments
$appointment_count = count($upcoming_appointments);

// Get meeting types
$meeting_types_query = "SELECT * FROM meeting_types WHERE user_id = '$user_id'";
$meeting_types_result = $conn->query($meeting_types_query);
$meeting_types = [];

if ($meeting_types_result && $meeting_types_result->num_rows > 0) {
    while ($row = $meeting_types_result->fetch_assoc()) {
        $meeting_types[] = $row;
    }
} else {
    // Insert default meeting types if none exist
    $default_types = [
        ['name' => '15 Minute Meeting', 'duration' => 15, 'color' => '#3788d8'],
        ['name' => '30 Minute Meeting', 'duration' => 30, 'color' => '#38b2ac'],
        ['name' => '60 Minute Meeting', 'duration' => 60, 'color' => '#805ad5']
    ];
    
    foreach ($default_types as $type) {
        $name = $type['name'];
        $duration = $type['duration'];
        $color = $type['color'];
        
        $insert_type = "INSERT INTO meeting_types (user_id, name, duration, color, description, is_active) 
                        VALUES ('$user_id', '$name', '$duration', '$color', 'Default meeting type', 1)";
        $conn->query($insert_type);
    }
    
    // Fetch the newly inserted types
    $meeting_types_result = $conn->query($meeting_types_query);
    while ($row = $meeting_types_result->fetch_assoc()) {
        $meeting_types[] = $row;
    }
}

// Generate booking link
$booking_link = "booking.php?user=" . $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Calendly Clone</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .stat-title {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-text);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .appointment-list {
            list-style: none;
        }

        .appointment-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-info {
            flex: 1;
        }

        .appointment-title {
            font-weight: 500;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .appointment-meta {
            font-size: 14px;
            color: var(--light-text);
            display: flex;
            gap: 15px;
        }

        .appointment-actions {
            display: flex;
            gap: 10px;
        }

        .meeting-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .meeting-type-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.3s;
        }

        .meeting-type-card:hover {
            box-shadow: var(--shadow);
        }

        .meeting-type-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .meeting-type-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .meeting-type-name {
            font-weight: 500;
            color: var(--dark-text);
        }

        .meeting-type-duration {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 15px;
        }

        .meeting-type-link {
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .meeting-type-link:hover {
            text-decoration: underline;
        }

        .meeting-type-link i {
            margin-left: 5px;
        }

        .availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .day-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 15px;
            box-shadow: var(--shadow);
        }

        .day-name {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .time-slots {
            list-style: none;
        }

        .time-slot {
            padding: 8px 0;
            font-size: 14px;
            color: var(--text-color);
            border-bottom: 1px dashed var(--border-color);
        }

        .time-slot:last-child {
            border-bottom: none;
        }

        .booking-link {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }

        .link-text {
            font-size: 14px;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .copy-btn {
            background-color: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-weight: 500;
            padding: 0 10px;
        }

        .copy-btn:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 48px;
            color: var(--light-text);
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 16px;
            color: var(--light-text);
            margin-bottom: 20px;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .meeting-types-grid {
                grid-template-columns: 1fr;
            }

            .availability-grid {
                grid-template-columns: 1fr;
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
                <a href="dashboard.php" class="menu-item active">
                    <i>📊</i> Dashboard
                </a>
                <a href="create_schedule.php" class="menu-item">
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
                <h1 class="page-title">Dashboard</h1>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo substr($user_data['name'], 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo $user_data['name']; ?></span>
                    <span class="dropdown-icon">▼</span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Upcoming Meetings</div>
                    <div class="stat-value"><?php echo $appointment_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Meeting Types</div>
                    <div class="stat-value"><?php echo count($meeting_types); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Available Days</div>
                    <div class="stat-value"><?php echo count($availability_by_day); ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Your Booking Link</h2>
                </div>
                <div class="card-body">
                    <p>Share this link with others to let them book meetings with you:</p>
                    <div class="booking-link">
                        <span class="link-text"><?php echo $booking_link; ?></span>
                        <button class="copy-btn" onclick="copyBookingLink()">Copy Link</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upcoming Appointments</h2>
                    <a href="#" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($upcoming_appointments) > 0): ?>
                        <ul class="appointment-list">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <li class="appointment-item">
                                    <div class="appointment-info">
                                        <div class="appointment-title"><?php echo $appointment['title']; ?></div>
                                        <div class="appointment-meta">
                                            <span><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></span>
                                            <span><?php echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . date('g:i A', strtotime($appointment['end_time'])); ?></span>
                                            <span>with <?php echo $appointment['visitor_name']; ?></span>
                                        </div>
                                    </div>
                                    <div class="appointment-actions">
                                        <button class="btn btn-outline btn-sm">Reschedule</button>
                                        <button class="btn btn-outline btn-sm">Cancel</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📅</div>
                            <div class="empty-state-text">You don't have any upcoming appointments.</div>
                            <a href="#" class="btn btn-primary">Share Your Booking Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Meeting Types</h2>
                    <a href="#" class="btn btn-outline btn-sm">Create New</a>
                </div>
                <div class="card-body">
                    <div class="meeting-types-grid">
                        <?php foreach ($meeting_types as $type): ?>
                            <div class="meeting-type-card">
                                <div class="meeting-type-header">
                                    <div class="meeting-type-color" style="background-color: <?php echo $type['color']; ?>"></div>
                                    <div class="meeting-type-name"><?php echo $type['name']; ?></div>
                                </div>
                                <div class="meeting-type-duration"><?php echo $type['duration']; ?> minutes</div>
                                <a href="<?php echo $booking_link . '&type=' . $type['id']; ?>" class="meeting-type-link">
                                    View booking page <i>→</i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Your Availability</h2>
                    <a href="create_schedule.php" class="btn btn-outline btn-sm">Edit</a>
                </div>
                <div class="card-body">
                    <div class="availability-grid">
                        <?php foreach ($availability_by_day as $day => $slots): ?>
                            <div class="day-card">
                                <div class="day-name"><?php echo $day_names[$day]; ?></div>
                                <ul class="time-slots">
                                    <?php foreach ($slots as $slot): ?>
                                        <li class="time-slot"><?php echo $slot['start'] . ' - ' . $slot['end']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyBookingLink() {
            const linkText = document.querySelector('.link-text').textContent;
            navigator.clipboard.writeText(linkText).then(() => {
                const copyBtn = document.querySelector('.copy-btn');
                copyBtn.textContent = 'Copied!';
                setTimeout(() => {
                    copyBtn.textContent = 'Copy Link';
                }, 2000);
            });
        }
    </script>
</body>
</html>

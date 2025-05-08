<?php
require_once 'db.php';

// Get user ID from URL
$user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
$meeting_type_id = isset($_GET['type']) ? intval($_GET['type']) : 0;

// Check if user exists
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_query);

if (!$user_result || $user_result->num_rows === 0) {
    // User not found
    header("Location: index.php");
    exit();
}

$user_data = $user_result->fetch_assoc();

// Get meeting type
$meeting_type = null;
if ($meeting_type_id > 0) {
    $meeting_type_query = "SELECT * FROM meeting_types WHERE id = '$meeting_type_id' AND user_id = '$user_id'";
    $meeting_type_result = $conn->query($meeting_type_query);
    
    if ($meeting_type_result && $meeting_type_result->num_rows > 0) {
        $meeting_type = $meeting_type_result->fetch_assoc();
    }
}

// If no specific meeting type is selected, get all meeting types
$meeting_types = [];
if (!$meeting_type) {
    $meeting_types_query = "SELECT * FROM meeting_types WHERE user_id = '$user_id' AND is_active = 1";
    $meeting_types_result = $conn->query($meeting_types_query);
    
    if ($meeting_types_result && $meeting_types_result->num_rows > 0) {
        while ($row = $meeting_types_result->fetch_assoc()) {
            $meeting_types[] = $row;
        }
    }
}

// Get user's availability
$availability_query = "SELECT * FROM availability WHERE user_id = '$user_id' AND is_available = 1";
$availability_result = $conn->query($availability_query);
$availability = [];

if ($availability_result && $availability_result->num_rows > 0) {
    while ($row = $availability_result->fetch_assoc()) {
        $availability[$row['day_of_week']] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
}

// Get existing appointments to block out times
$appointments_query = "SELECT appointment_date, start_time, end_time FROM appointments WHERE user_id = '$user_id' AND status = 'scheduled'";
$appointments_result = $conn->query($appointments_query);
$booked_slots = [];

if ($appointments_result && $appointments_result->num_rows > 0) {
    while ($row = $appointments_result->fetch_assoc()) {
        $date = $row['appointment_date'];
        if (!isset($booked_slots[$date])) {
            $booked_slots[$date] = [];
        }
        $booked_slots[$date][] = [
            'start' => $row['start_time'],
            'end' => $row['end_time']
        ];
    }
}

// Process booking form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        // Create a temporary user account
        $name = sanitize($conn, $_POST['name']);
        $email = sanitize($conn, $_POST['email']);
        
        // Check if user with this email already exists
        $check_user_query = "SELECT * FROM users WHERE email = '$email'";
        $check_user_result = $conn->query($check_user_query);
        
        if ($check_user_result && $check_user_result->num_rows > 0) {
            $visitor = $check_user_result->fetch_assoc();
            $visitor_id = $visitor['id'];
        } else {
            // Create new user
            $password = password_hash(uniqid(), PASSWORD_DEFAULT); // Generate random password
            $insert_user_query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
            
            if ($conn->query($insert_user_query)) {
                $visitor_id = $conn->insert_id;
            } else {
                $error_message = "Error creating user account: " . $conn->error;
            }
        }
    } else {
        $visitor_id = $_SESSION['user_id'];
    }
    
    if (empty($error_message)) {
        $appointment_date = sanitize($conn, $_POST['appointment_date']);
        $start_time = sanitize($conn, $_POST['start_time']);
        $meeting_type_id = intval($_POST['meeting_type_id']);
        $title = sanitize($conn, $_POST['title']);
        $description = sanitize($conn, $_POST['description']);
        
        // Get meeting duration
        $duration_query = "SELECT duration FROM meeting_types WHERE id = '$meeting_type_id'";
        $duration_result = $conn->query($duration_query);
        
        if ($duration_result && $duration_result->num_rows > 0) {
            $duration_row = $duration_result->fetch_assoc();
            $duration = $duration_row['duration'];
            
            // Calculate end time
            $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
            
            // Insert appointment
            $insert_appointment_query = "INSERT INTO appointments (user_id, visitor_id, appointment_date, start_time, end_time, title, description, status) 
                                        VALUES ('$user_id', '$visitor_id', '$appointment_date', '$start_time', '$end_time', '$title', '$description', 'scheduled')";
            
            if ($conn->query($insert_appointment_query)) {
                $success_message = "Appointment booked successfully!";
                
                // Create notification for calendar owner
                $notification_message = "New appointment: $title on " . date('M d, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($start_time));
                $appointment_id = $conn->insert_id;
                
                $insert_notification_query = "INSERT INTO notifications (user_id, appointment_id, message) 
                                            VALUES ('$user_id', '$appointment_id', '$notification_message')";
                $conn->query($insert_notification_query);
            } else {
                $error_message = "Error booking appointment: " . $conn->error;
            }
        } else {
            $error_message = "Invalid meeting type";
        }
    }
}

// Day names
$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Generate dates for the next 30 days
$dates = [];
$today = new DateTime();
for ($i = 0; $i < 30; $i++) {
    $date = clone $today;
    $date->modify("+$i days");
    $dates[] = $date;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Meeting with <?php echo $user_data['name']; ?> - Calendly Clone</title>
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

        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .booking-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .booking-title {
            font-size: 28px;
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        .booking-subtitle {
            font-size: 16px;
            color: var(--light-text);
        }

        .booking-steps {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }

        .booking-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 20px;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .step-label {
            font-size: 14px;
            color: var(--text-color);
        }

        .step-active .step-number {
            background-color: var(--primary-color);
        }

        .step-active .step-label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .booking-content {
            display: flex;
            gap: 30px;
        }

        .booking-sidebar {
            width: 300px;
            flex-shrink: 0;
        }

        .booking-main {
            flex: 1;
        }

        .user-card {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
            margin: 0 auto 15px;
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            text-align: center;
            margin-bottom: 5px;
        }

        .user-title {
            font-size: 14px;
            color: var(--light-text);
            text-align: center;
            margin-bottom: 15px;
        }

        .meeting-types {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .meeting-type {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .meeting-type:last-child {
            border-bottom: none;
        }

        .meeting-type:hover {
            background-color: rgba(0, 105, 255, 0.05);
        }

        .meeting-type.active {
            background-color: rgba(0, 105, 255, 0.1);
            border-left: 3px solid var(--primary-color);
        }

        .meeting-type-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .meeting-type-color {
            width: 12px;
            height: 12px;
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
        }

        .calendar-container {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background-color: var(--white);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .calendar-nav-btn:hover {
            background-color: var(--light-bg);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 500;
            color: var(--light-text);
            padding: 5px 0;
            font-size: 14px;
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .calendar-day:hover {
            background-color: rgba(0, 105, 255, 0.05);
            border-color: var(--primary-color);
        }

        .calendar-day.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .calendar-day.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: var(--light-bg);
        }

        .calendar-day-number {
            font-weight: 500;
            font-size: 16px;
        }

        .calendar-day-name {
            font-size: 12px;
            margin-top: 5px;
        }

        .time-slots {
            margin-top: 30px;
        }

        .time-slots-header {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 15px;
        }

        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }

        .time-slot {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover {
            background-color: rgba(0, 105, 255, 0.05);
            border-color: var(--primary-color);
        }

        .time-slot.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .time-slot.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: var(--light-bg);
        }

        .booking-form {
            margin-top: 30px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 20px;
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 12px 20px;
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

        .btn-block {
            display: block;
            width: 100%;
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

        .confirmation {
            text-align: center;
            padding: 40px 20px;
        }

        .confirmation-icon {
            font-size: 64px;
            color: var(--success);
            margin-bottom: 20px;
        }

        .confirmation-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        .confirmation-message {
            font-size: 16px;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .booking-content {
                flex-direction: column;
            }

            .booking-sidebar {
                width: 100%;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
            }

            .calendar-day-header {
                font-size: 12px;
            }

            .calendar-day-number {
                font-size: 14px;
            }

            .calendar-day-name {
                display: none;
            }

            .time-slots-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="booking-header">
            <a href="index.php" class="logo">Calendly</a>
            <h1 class="booking-title">Book a Meeting with <?php echo $user_data['name']; ?></h1>
            <p class="booking-subtitle">Select a meeting type, date, and time to schedule your appointment.</p>
        </div>

        <div class="booking-steps">
            <div class="booking-step <?php echo !$meeting_type ? 'step-active' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Select Meeting Type</div>
            </div>
            <div class="booking-step <?php echo $meeting_type && empty($success_message) ? 'step-active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Select Date & Time</div>
            </div>
            <div class="booking-step <?php echo !empty($success_message) ? 'step-active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="confirmation">
                <div class="confirmation-icon">✓</div>
                <h2 class="confirmation-title">Booking Confirmed!</h2>
                <p class="confirmation-message">Your meeting has been scheduled successfully. You will receive a confirmation email shortly.</p>
                <a href="index.php" class="btn btn-primary">Return to Home</a>
            </div>
        <?php else: ?>
            <div class="booking-content">
                <div class="booking-sidebar">
                    <div class="user-card">
                        <div class="user-avatar">
                            <?php echo substr($user_data['name'], 0, 1); ?>
                        </div>
                        <div class="user-name"><?php echo $user_data['name']; ?></div>
                        <div class="user-title"><?php echo $user_data['job_title'] ?? 'Calendar Owner'; ?></div>
                    </div>

                    <?php if (!$meeting_type && count($meeting_types) > 0): ?>
                        <div class="meeting-types">
                            <?php foreach ($meeting_types as $type): ?>
                                <a href="booking.php?user=<?php echo $user_id; ?>&type=<?php echo $type['id']; ?>" class="meeting-type">
                                    <div class="meeting-type-header">
                                        <div class="meeting-type-color" style="background-color: <?php echo $type['color']; ?>"></div>
                                        <div class="meeting-type-name"><?php echo $type['name']; ?></div>
                                    </div>
                                    <div class="meeting-type-duration"><?php echo $type['duration']; ?> minutes</div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="booking-main">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-error">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($meeting_type): ?>
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <h2 class="calendar-title">Select a Date</h2>
                                <div class="calendar-nav">
                                    <button class="calendar-nav-btn" onclick="prevWeek()">←</button>
                                    <button class="calendar-nav-btn" onclick="nextWeek()">→</button>
                                </div>
                            </div>

                            <div class="calendar-grid" id="calendar-days-header">
                                <?php foreach ($day_names as $day): ?>
                                    <div class="calendar-day-header"><?php echo substr($day, 0, 3); ?></div>
                                <?php endforeach; ?>
                            </div>

                            <div class="calendar-grid" id="calendar-days">
                                <?php 
                                $current_week = 0;
                                foreach ($dates as $index => $date): 
                                    $day_of_week = $date->format('w'); // 0 (Sunday) to 6 (Saturday)
                                    $is_available = isset($availability[$day_of_week]);
                                    $date_string = $date->format('Y-m-d');
                                    $is_past = $date < new DateTime('today');
                                ?>
                                    <div class="calendar-day <?php echo $is_available && !$is_past ? '' : 'disabled'; ?>" 
                                         data-date="<?php echo $date_string; ?>"
                                         data-week="<?php echo floor($index / 7); ?>"
                                         onclick="<?php echo $is_available && !$is_past ? "selectDate('$date_string')" : ''; ?>">
                                        <div class="calendar-day-number"><?php echo $date->format('j'); ?></div>
                                        <div class="calendar-day-name"><?php echo $date->format('D'); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="time-slots" id="time-slots-container" style="display: none;">
                                <h3 class="time-slots-header">Select a Time</h3>
                                <div class="time-slots-grid" id="time-slots-grid"></div>
                            </div>
                        </div>

                        <div class="booking-form" id="booking-form" style="display: none;">
                            <h3 class="form-title">Complete Your Booking</h3>
                            <form action="booking.php?user=<?php echo $user_id; ?>&type=<?php echo $meeting_type['id']; ?>" method="POST">
                                <input type="hidden" name="appointment_date" id="appointment_date">
                                <input type="hidden" name="start_time" id="start_time">
                                <input type="hidden" name="meeting_type_id" value="<?php echo $meeting_type['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="title" class="form-label">Meeting Title</label>
                                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $meeting_type['name']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" id="name" name="name" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description" class="form-label">Additional Notes</label>
                                    <textarea id="description" name="description" class="form-control"></textarea>
                                </div>
                                
                                <button type="submit" name="book_appointment" class="btn btn-primary btn-block">Schedule Meeting</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Calendar navigation
        let currentWeek = 0;
        const totalWeeks = Math.ceil(<?php echo count($dates); ?> / 7);
        
        function showWeek(weekNum) {
            const days = document.querySelectorAll('.calendar-day');
            days.forEach(day => {
                const week = parseInt(day.getAttribute('data-week'));
                if (week === weekNum) {
                    day.style.display = 'flex';
                } else {
                    day.style.display = 'none';
                }
            });
        }
        
        function prevWeek() {
            if (currentWeek > 0) {
                currentWeek--;
                showWeek(currentWeek);
            }
        }
        
        function nextWeek() {
            if (currentWeek < totalWeeks - 1) {
                currentWeek++;
                showWeek(currentWeek);
            }
        }
        
        // Initialize calendar
        showWeek(currentWeek);
        
        // Date selection
        let selectedDate = null;
        
        function selectDate(date) {
            // Clear previous selection
            const days = document.querySelectorAll('.calendar-day');
            days.forEach(day => {
                day.classList.remove('active');
            });
            
            // Set new selection
            const selectedDay = document.querySelector(`.calendar-day[data-date="${date}"]`);
            if (selectedDay) {
                selectedDay.classList.add('active');
                selectedDate = date;
                
                // Show time slots
                showTimeSlots(date);
            }
        }
        
        // Time slots generation
        function showTimeSlots(date) {
            const container = document.getElementById('time-slots-container');
            const grid = document.getElementById('time-slots-grid');
            
            // Show container
            container.style.display = 'block';
            
            // Clear previous slots
            grid.innerHTML = '';
            
            // Get day of week
            const dayOfWeek = new Date(date).getDay();
            
            // Check if day is available
            <?php echo "const availability = " . json_encode($availability) . ";\n"; ?>
            <?php echo "const bookedSlots = " . json_encode($booked_slots) . ";\n"; ?>
            
            if (availability[dayOfWeek]) {
                const startTime = availability[dayOfWeek].start_time;
                const endTime = availability[dayOfWeek].end_time;
                
                // Generate time slots
                const slots = generateTimeSlots(startTime, endTime, <?php echo $meeting_type ? $meeting_type['duration'] : 30; ?>);
                
                // Check for booked slots
                const dateBookings = bookedSlots[date] || [];
                
                // Create slot elements
                slots.forEach(slot => {
                    const isBooked = isTimeSlotBooked(slot.start, slot.end, dateBookings);
                    
                    const slotElement = document.createElement('div');
                    slotElement.className = `time-slot ${isBooked ? 'disabled' : ''}`;
                    slotElement.textContent = slot.display;
                    
                    if (!isBooked) {
                        slotElement.onclick = function() {
                            selectTimeSlot(this, slot.start);
                        };
                    }
                    
                    grid.appendChild(slotElement);
                });
            } else {
                grid.innerHTML = '<p>No available time slots for this day.</p>';
            }
        }
        
        function generateTimeSlots(startTime, endTime, duration) {
            const slots = [];
            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);
            
            let current = new Date(start);
            
            while (current < end) {
                const slotStart = current.toTimeString().substring(0, 8);
                
                // Calculate end time
                const slotEnd = new Date(new Date(current).setMinutes(current.getMinutes() + duration)).toTimeString().substring(0, 8);
                
                // Check if slot end is within availability
                if (new Date(`2000-01-01T${slotEnd}`) <= end) {
                    slots.push({
                        start: slotStart,
                        end: slotEnd,
                        display: formatTime(slotStart)
                    });
                }
                
                // Move to next slot
                current.setMinutes(current.getMinutes() + duration);
            }
            
            return slots;
        }
        
        function formatTime(timeString) {
            const date = new Date(`2000-01-01T${timeString}`);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
        
        function isTimeSlotBooked(start, end, bookings) {
            for (const booking of bookings) {
                // Check if slot overlaps with any booking
                if (
                    (start >= booking.start && start < booking.end) ||
                    (end > booking.start && end <= booking.end) ||
                    (start <= booking.start && end >= booking.end)
                ) {
                    return true;
                }
            }
            return false;
        }
        
        // Time slot selection
        let selectedTime = null;
        
        function selectTimeSlot(element, time) {
            // Clear previous selection
            const slots = document.querySelectorAll('.time-slot');
            slots.forEach(slot => {
                slot.classList.remove('active');
            });
            
            // Set new selection
            element.classList.add('active');
            selectedTime = time;
            
            // Show booking form
            document.getElementById('booking-form').style.display = 'block';
            document.getElementById('appointment_date').value = selectedDate;
            document.getElementById('start_time').value = selectedTime;
            
            // Scroll to form
            document.getElementById('booking-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>

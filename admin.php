<?php
session_start();

// Session Guard: Redirect to login.php if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$event_errors = [];

// 1. Handle Adding a New Event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $title = clean_input($_POST['title'] ?? '');
    $speaker = clean_input($_POST['speaker'] ?? '');
    $event_date = clean_input($_POST['event_date'] ?? '');
    $venue = clean_input($_POST['venue'] ?? '');
    $max_capacity = isset($_POST['max_capacity']) ? (int)$_POST['max_capacity'] : 0;

    if (empty($title) || strlen($title) < 5) {
        $event_errors[] = "Event title must be at least 5 characters.";
    }
    if (empty($speaker)) {
        $event_errors[] = "Speaker name is required.";
    }
    if (empty($event_date)) {
        $event_errors[] = "Valid event date is required.";
    }
    if (empty($venue)) {
        $event_errors[] = "Venue is required.";
    }
    if ($max_capacity < 1 || $max_capacity > 1000) {
        $event_errors[] = "Max capacity must be between 1 and 1000.";
    }

    if (empty($event_errors)) {
        $ins_ev = $conn->prepare("INSERT INTO events (title, speaker, event_date, venue, max_capacity) VALUES (?, ?, ?, ?, ?)");
        $ins_ev->bind_param("ssssi", $title, $speaker, $event_date, $venue, $max_capacity);
        $ins_ev->execute();
        $ins_ev->close();
        header("Location: admin.php?event_added=1");
        exit();
    }
}

// 2. Handle Deleting an Event (Cascades to registrations automatically)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_event_id'])) {
    $del_ev_id = (int)$_POST['delete_event_id'];
    $del_ev = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $del_ev->bind_param("i", $del_ev_id);
    $del_ev->execute();
    $del_ev->close();
    header("Location: admin.php?event_deleted=1");
    exit();
}

// 3. Handle Deleting a Student Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_reg_id'])) {
    $del_id = (int)$_POST['delete_reg_id'];
    $del_stmt = $conn->prepare("DELETE FROM registrations WHERE reg_id = ?");
    $del_stmt->bind_param("i", $del_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: admin.php?deleted=1");
    exit();
}

$filter_event = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Fetch registrations with optional event filter
if ($filter_event > 0) {
    $stmt = $conn->prepare("SELECT r.*, e.title FROM registrations r JOIN events e ON r.event_id = e.event_id WHERE r.event_id = ? ORDER BY r.registered_at DESC");
    $stmt->bind_param("i", $filter_event);
    $stmt->execute();
    $registrations = $stmt->get_result();
} else {
    $registrations = $conn->query("SELECT r.*, e.title FROM registrations r JOIN events e ON r.event_id = e.event_id ORDER BY r.registered_at DESC");
}

// Fetch all events with registration counts for Event Management table
$all_events = $conn->query("SELECT e.*, COUNT(r.reg_id) AS booked_seats 
                            FROM events e 
                            LEFT JOIN registrations r ON e.event_id = r.event_id 
                            GROUP BY e.event_id 
                            ORDER BY e.event_date ASC");

$events_dropdown = $conn->query("SELECT event_id, title FROM events ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Manage Events & Registrations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h2>CampusEvents Admin</h2>
    <div>
        <a href="index.php">Events Catalog</a>
        <a href="about.html">Guidelines</a>
        <a href="register.html">Register</a>
        <a href="ticket.php">Find My Pass</a>
        <a href="admin.php" class="active">Admin Panel</a>
        <a href="login.php?logout=1" style="color: #fca5a5;">Logout (<?php echo htmlspecialchars($_SESSION['admin_user']); ?>)</a>
    </div>
</div>

<div class="container">

    <!-- Status Messages -->
    <?php if (isset($_GET['event_added'])): ?>
        <div class="alert-success">New workshop added to the catalog.</div>
    <?php endif; ?>
    <?php if (isset($_GET['event_deleted'])): ?>
        <div class="alert-success">Workshop and its associated registrations have been removed.</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert-success">Student registration removed and seat released.</div>
    <?php endif; ?>
    <?php if (!empty($event_errors)): ?>
        <div class="alert-error">
            <?php foreach ($event_errors as $err) echo "<div>• " . htmlspecialchars($err) . "</div>"; ?>
        </div>
    <?php endif; ?>

    <!-- SECTION 1: ADD & MANAGE WORKSHOPS -->
    <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 1.5rem; align-items: start; margin-bottom: 2.5rem;">
        
        <!-- Add New Workshop Form -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">+ Add New Workshop</h3>
            <form method="POST" action="admin.php">
                <input type="hidden" name="add_event" value="1">
                <div class="form-group">
                    <label>Workshop Title *</label>
                    <input type="text" name="title" placeholder="e.g. Cloud Computing with AWS" required>
                </div>
                <div class="form-group">
                    <label>Speaker / Instructor *</label>
                    <input type="text" name="speaker" placeholder="e.g. Dr. Vivek Menon" required>
                </div>
                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Venue *</label>
                    <input type="text" name="venue" placeholder="e.g. IT Seminar Hall" required>
                </div>
                <div class="form-group">
                    <label>Max Seat Capacity *</label>
                    <input type="number" name="max_capacity" min="1" max="1000" placeholder="50" required>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 0;">Create Workshop</button>
            </form>
        </div>

        <!-- Existing Workshops Table -->
        <div>
            <h2>Manage Existing Workshops</h2>
            <table>
                <thead>
                    <tr>
                        <th>Workshop Title</th>
                        <th>Date & Venue</th>
                        <th>Seats Booked</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all_events->num_rows > 0): ?>
                        <?php while ($ev_row = $all_events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ev_row['title']); ?></strong><br>
                                    <small>Speaker: <?php echo htmlspecialchars($ev_row['speaker']); ?></small>
                                </td>
                                <td>
                                    <?php echo date("d M Y", strtotime($ev_row['event_date'])); ?><br>
                                    <small><?php echo htmlspecialchars($ev_row['venue']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $ev_row['booked_seats']; ?> / <?php echo $ev_row['max_capacity']; ?></strong>
                                </td>
                                <td>
                                    <form method="POST" action="admin.php" onsubmit="return confirm('Delete this workshop? All student registrations for this workshop will also be deleted.');">
                                        <input type="hidden" name="delete_event_id" value="<?php echo $ev_row['event_id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete Event</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No workshops available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr style="border: 0; border-top: 2px solid #e2e8f0; margin: 2rem 0;">

    <!-- SECTION 2: MANAGE STUDENT REGISTRATIONS -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <h2>Manage Attendee Registrations</h2>
        
        <form method="GET" action="admin.php" style="display:flex; gap:0.5rem; align-items:center;">
            <select name="event_id" style="padding:0.5rem; border-radius:4px; border:1px solid #cbd5e1;">
                <option value="0">All Workshops</option>
                <?php while ($ev = $events_dropdown->fetch_assoc()): ?>
                    <option value="<?php echo $ev['event_id']; ?>" <?php if ($filter_event == $ev['event_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($ev['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn" style="margin-top:0; padding:0.5rem 1rem;">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pass ID</th>
                <th>Student Name</th>
                <th>Roll No (Dept)</th>
                <th>Workshop</th>
                <th>Contact</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registrations->num_rows > 0): ?>
                <?php while ($row = $registrations->fetch_assoc()): ?>
                    <tr>
                        <td><strong><a href="ticket.php?pass=<?php echo urlencode($row['pass_code']); ?>"><?php echo htmlspecialchars($row['pass_code']); ?></a></strong></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['roll_no']) . " (" . htmlspecialchars($row['department']) . ")"; ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                        <td>
                            <form method="POST" action="admin.php" onsubmit="return confirm('Cancel this student registration?');">
                                <input type="hidden" name="delete_reg_id" value="<?php echo $row['reg_id']; ?>">
                                <button type="submit" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No registrations found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

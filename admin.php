<?php
// This file is included in adminfrontend.php, so session and db.php are already included.

// CRITICAL: Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$result = $conn->query("SELECT event_id, title, date FROM events ORDER BY date ASC");

echo '<div class="container mt-4">';
echo '<h4 class="text-danger mb-3">📅 Current Events</h4>';

if ($result->num_rows > 0) {
    echo '<table class="table table-striped table-bordered shadow-sm">';
    echo '<thead class="table-danger">';
    echo '<tr><th>ID</th><th>Title</th><th>Date</th><th>Actions</th></tr>';
    echo '</thead><tbody>';

    while ($row = $result->fetch_assoc()) {
        $event_id_safe = (int)$row['event_id'];
        $title_safe = htmlspecialchars($row['title']);
        $date_safe = htmlspecialchars($row['date']);

        echo '<tr>';
        echo "<td>{$event_id_safe}</td>";
        echo "<td>{$title_safe}</td>";
        echo "<td>{$date_safe}</td>";
        echo '<td>';
        echo "<a class='btn btn-sm btn-warning me-2' href='edit_event.php?id={$event_id_safe}'>
                ✏️ Edit
              </a>";
        echo "<a class='btn btn-sm btn-danger' href='delete_event.php?id={$event_id_safe}' onclick=\"return confirm('Are you sure you want to delete this event?');\">
                🗑️ Delete
              </a>";
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<p class="alert alert-info">No events have been added yet.</p>';
}

echo '</div>';
?>

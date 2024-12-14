<?php
session_start();
require_once "includes/database.php";
require_once "models/TownHall.php";

if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$townhall = new TownHall($db);

$page_title = "Virtual Town Halls";
include_once "header.php";
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Virtual Town Halls</h2>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTownHallModal">
                    Schedule New Town Hall
                </button>
                <?php endif; ?>
            </div>

            <!-- Upcoming Town Halls -->
            <div class="row">
                <?php
                $stmt = $townhall->getUpcoming();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                            <ul class="list-unstyled">
                                <li><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($row['scheduled_date'])); ?></li>
                                <li><strong>Duration:</strong> <?php echo $row['duration_minutes']; ?> minutes</li>
                                <li><strong>Status:</strong> <?php echo ucfirst($row['status']); ?></li>
                                <li><strong>Participants:</strong> <?php echo $row['participant_count']; ?>/100</li>
                            </ul>
                            <?php if ($row['participant_count'] < 100): ?>
                            <button class="btn btn-primary register-btn" data-townhall-id="<?php echo $row['town_hall_id']; ?>">
                                Register
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled>Full</button>
                            <?php endif; ?>
                            <?php if (strtotime($row['scheduled_date']) <= time() + 300): // 5 minutes before start ?>
                            <a href="<?php echo htmlspecialchars($row['meeting_link']); ?>" class="btn btn-success" target="_blank">
                                Join Meeting
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Town Hall Modal -->
<div class="modal fade" id="createTownHallModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule New Town Hall</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createTownHallForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">Date and Time</label>
                        <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="15" max="180" value="60" required>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_link" class="form-label">Meeting Link</label>
                        <input type="url" class="form-control" id="meeting_link" name="meeting_link" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitTownHall">Schedule Town Hall</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle town hall registration
    document.querySelectorAll('.register-btn').forEach(button => {
        button.addEventListener('click', function() {
            const townhallId = this.dataset.townhallId;
            fetch('api/register_townhall.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    town_hall_id: townhallId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Successfully registered for the town hall!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to register for the town hall.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while registering for the town hall.');
            });
        });
    });

    // Handle town hall creation
    document.getElementById('submitTownHall').addEventListener('click', function() {
        const form = document.getElementById('createTownHallForm');
        const formData = new FormData(form);
        
        fetch('api/create_townhall.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Town hall scheduled successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to schedule town hall.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while scheduling the town hall.');
        });
    });
});
</script>

<?php include_once "footer.php"; ?>

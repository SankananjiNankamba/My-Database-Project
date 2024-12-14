<?php
require_once 'includes/session.php';
require_once 'includes/session.php';
session_start();
require_once "config/database.php";
require_once "models/Initiative.php";

if (!isset($_SESSION['username'])) {
    header("location:index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("location:initiatives.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$initiative = new Initiative($db);

$initiative_id = $_GET['id'];
$initiative_data = $initiative->getById($initiative_id);

if (!$initiative_data) {
    header("location:initiatives.php");
    exit();
}

$page_title = "Initiative Details: " . htmlspecialchars($initiative_data['title']);
include_once "header.php";
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="initiatives.php">Initiatives</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($initiative_data['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title"><?php echo htmlspecialchars($initiative_data['title']); ?></h2>
                        <span class="badge bg-<?php 
                            echo $initiative_data['status'] === 'completed' ? 'success' : 
                                ($initiative_data['status'] === 'in_progress' ? 'primary' : 'warning'); 
                            ?> fs-6">
                            <?php echo ucfirst(str_replace('_', ' ', $initiative_data['status'])); ?>
                        </span>
                    </div>

                    <div class="progress mt-3 mb-3">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $initiative_data['progress_percentage']; ?>%"
                             aria-valuenow="<?php echo $initiative_data['progress_percentage']; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo $initiative_data['progress_percentage']; ?>%
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Details</h5>
                            <ul class="list-unstyled">
                                <li><strong>Category:</strong> <?php echo ucfirst($initiative_data['category']); ?></li>
                                <li><strong>Location:</strong> <?php echo htmlspecialchars($initiative_data['location']); ?></li>
                                <li><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($initiative_data['start_date'])); ?></li>
                                <li><strong>End Date:</strong> <?php echo date('F j, Y', strtotime($initiative_data['end_date'])); ?></li>
                                <li><strong>Budget:</strong> ZMW <?php echo number_format($initiative_data['budget'], 2); ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Impact Area</h5>
                            <p><?php echo nl2br(htmlspecialchars($initiative_data['impact_area'])); ?></p>
                        </div>
                    </div>

                    <h5>Description</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($initiative_data['description'])); ?></p>

                    <?php if ($documents = $initiative->getDocuments($initiative_id)): ?>
                    <h5>Supporting Documents</h5>
                    <ul class="list-group">
                        <?php foreach ($documents as $doc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($doc['file_name']); ?></span>
                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                View Document
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress Updates -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Progress Updates</h5>
                    <?php 
                    $updates = $initiative->getUpdates($initiative_id);
                    if ($updates && $updates->rowCount() > 0):
                    ?>
                    <div class="timeline">
                        <?php while ($update = $updates->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo date('M j, Y', strtotime($update['updated_at'])); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <h6>Progress: <?php echo $update['progress_percentage']; ?>%</h6>
                                    <span class="badge bg-<?php 
                                        echo $update['status'] === 'completed' ? 'success' : 
                                            ($update['status'] === 'in_progress' ? 'primary' : 'warning'); 
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $update['status'])); ?>
                                    </span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($update['notes'])); ?></p>
                                <small class="text-muted">Updated by <?php echo htmlspecialchars($update['updated_by']); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No updates available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Initiative Stats -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Initiative Stats</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Days Remaining
                            <?php 
                            $days_remaining = max(0, floor((strtotime($initiative_data['end_date']) - time()) / (60 * 60 * 24)));
                            ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $days_remaining; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Updates
                            <span class="badge bg-primary rounded-pill"><?php echo $initiative->getUpdateCount($initiative_id); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Documents
                            <span class="badge bg-primary rounded-pill"><?php echo $initiative->getDocumentCount($initiative_id); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Initiative Timeline -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Timeline</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <small class="text-muted">Created</small><br>
                            <?php echo date('F j, Y', strtotime($initiative_data['created_at'])); ?><br>
                            by <?php echo htmlspecialchars($initiative_data['created_by']); ?>
                        </li>
                        <?php if ($initiative_data['updated_at']): ?>
                        <li class="list-group-item">
                            <small class="text-muted">Last Updated</small><br>
                            <?php echo date('F j, Y', strtotime($initiative_data['updated_at'])); ?><br>
                            by <?php echo htmlspecialchars($initiative_data['updated_by']); ?>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            <small class="text-muted">Expected Completion</small><br>
                            <?php echo date('F j, Y', strtotime($initiative_data['end_date'])); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 30px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: -30px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item:last-child:before {
    bottom: 0;
}

.timeline-item:after {
    content: '';
    position: absolute;
    left: -4px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #007bff;
}

.timeline-date {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}
</style>

<?php include_once "footer.php"; ?>

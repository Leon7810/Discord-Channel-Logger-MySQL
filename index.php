<?php
// Load configuration from config.json
$config = json_decode(file_get_contents('config.json'), true);

if ($config === null) {
    die("Error loading config.json");
}

// Database connection settings from config.json
$host = $config['database']['host'];
$user = $config['database']['user'];
$password = $config['database']['password'];
$database = $config['database']['name'];

// Connect to the database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Discord bot token from config.json
$discord_bot_token = $config['bot']['token'];  // Replace with your Discord bot token

// Function to fetch a username from Discord API using a user ID
function fetchUsername($user_id, $discord_bot_token) {
    $url = "https://discord.com/api/v10/users/{$user_id}";  // Discord API endpoint (use newest)
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$discord_bot_token}",
            "User-Agent: PHP/DiscordUserFetcher",
        ],
    ]);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        curl_close($curl);
        return "Unknown";  // Return "Unknown" if cURL error occurs
    }

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        curl_close($curl);
        return "Unknown";  // Return "Unknown" if HTTP status is not 200
    }

    curl_close($curl);

    $user_data = json_decode($response, true);
    return $user_data['username'] ?? "Unknown";  // Return the username or "Unknown" if not found
}

// Set pagination variables
$records_per_page = $config['dashboard']['records_per_page'];  // Number of records to display per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;  // Current page
$start_from = ($page - 1) * $records_per_page;  // Start point for pagination

// Search and filter variables
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$timestamp_filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';

// Build SQL query
$sql = "SELECT * FROM discord_messages";

if ($search_query !== '') {
    $sql .= " WHERE content LIKE '%$search_query%' OR author LIKE '%$search_query%'";
}

if ($timestamp_filter === 'newest') {
    $sql .= " ORDER BY timestamp DESC";
} elseif ($timestamp_filter === 'oldest') {
    $sql .= " ORDER BY timestamp ASC";
}

// Add pagination limits
$sql .= " LIMIT $start_from, $records_per_page";

// Execute the query
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}

// Calculate total pages for pagination
$total_records_sql = "SELECT COUNT(*) AS total FROM discord_messages";
$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result ? $total_records_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_records / $records_per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discord Messages</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1>Discord Message Logger</h1>

    <form method="GET" class="form-inline mb-3">
        <input type="text" class="form-control mr-2" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search">
        <select name="filter" class="form-control mr-2">
            <option value="newest" <?php echo $timestamp_filter === 'newest' ? 'selected' : ''; ?>>Newest</option>
            <option value="oldest" <?php echo $timestamp_filter === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
        </select>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

    <table class="table table-bordered" id="messagesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>Author</th>
                <th>Content</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['timestamp']; ?></td>
                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                        <td><?php echo htmlspecialchars(preg_replace_callback('/<@(\d+)>/', function ($matches) use ($discord_bot_token) {
                            return '@' . fetchUsername($matches[1], $discord_bot_token);
                        }, $row['content'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&filter=<?php echo urlencode($timestamp_filter); ?>" class="page-link"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
        <p class="text-center text-muted">This data refreshes every 5 seconds.</p>
    </nav>
</div>

<center class="mb-4">Created by <a href="https://github.com/Leon7810">LeonKong</a>.</center>

<script>
$(document).ready(function() {
    function loadNewMessages() {
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                search: $('input[name="search"]').val(),
                filter: $('select[name="filter"]').val()
            },
            success: function(data) { 
                $('#messagesTable tbody').html($(data).find('#messagesTable tbody').html());
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error:", textStatus, errorThrown);
            },
        });
    }

    setInterval(loadNewMessages, 5000);  // 10 seconds
});

</script>

</body>
</html>

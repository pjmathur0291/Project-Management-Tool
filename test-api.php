<?php
echo "<h1>API Test Page</h1>";

// Test the projects API directly
echo "<h2>Testing Projects API</h2>";

// Test GET request
echo "<h3>Testing GET request (list projects)</h3>";
$getUrl = 'http://localhost/management-tool/api/projects.php';
$getResponse = file_get_contents($getUrl);

if ($getResponse !== false) {
    echo "<p style='color: green;'>✓ GET request successful</p>";
    $data = json_decode($getResponse, true);
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ GET request failed</p>";
}

// Test POST request
echo "<h3>Testing POST request (create project)</h3>";
$postData = [
    'name' => 'Test Project ' . date('Y-m-d H:i:s'),
    'description' => 'Test project created via API test',
    'status' => 'pending',
    'priority' => 'medium',
    'start_date' => date('Y-m-d'),
    'end_date' => '',
    'manager_id' => '2'
];

$postUrl = 'http://localhost/management-tool/api/projects.php';
$postContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($postData)
    ]
]);

$postResponse = file_get_contents($postUrl, false, $postContext);

if ($postResponse !== false) {
    echo "<p style='color: green;'>✓ POST request successful</p>";
    $data = json_decode($postResponse, true);
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ POST request failed</p>";
}

echo "<hr>";
echo "<h2>PHP Info</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Error Reporting:</strong> " . (error_reporting() ? 'Enabled' : 'Disabled') . "</p>";
echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p><a href='debug.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Debug Page</a></p>";
echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main App</a></p>";
?>

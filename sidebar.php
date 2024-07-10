<?php
// Sidebar links based on user role
$sidebar_links = [
    'admin' => [
        ['url' => 'tasks.php', 'label' => 'Task Management'],
        ['url' => 'leave.php', 'label' => 'Leave Management'],
        ['url' => 'users.php', 'label' => 'User Management'], // Example: Add more links for admin
    ],
    'manager' => [
        ['url' => 'tasks.php', 'label' => 'Task Management'],
        ['url' => 'leave.php', 'label' => 'Leave Management'],
    ],
    'employee' => [
        ['url' => 'leave.php', 'label' => 'Leave Management'],
    ],
];
?>

<div class="sidebar">
    <ul>
        <?php
        foreach ($sidebar_links[$role] as $link) {
            echo '<li><a href="' . $link['url'] . '">' . $link['label'] . '</a></li>';
        }
        ?>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

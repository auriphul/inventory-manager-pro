<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventory Manager Dashboard</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Plugin CSS -->
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../../assets/css/fullscreen.css'; ?>">

    <?php wp_head(); ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Main Sidebar -->
    <?php include __DIR__ . '/tabs-nav.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        <?php
        // Dynamically include content page
        if (!empty($template_file)) {
            include $template_file;
        } else {
            echo '<p>No content available.</p>';
        }
        ?>
    </div>

</div>

<!-- AdminLTE JS -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<?php wp_footer(); ?>
</body>
</html>

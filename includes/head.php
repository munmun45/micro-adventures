<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'TravelApp'; ?></title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Common CSS -->
    <link rel="stylesheet" href="css/common.css">
    
    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="css/<?php echo $pageCSS; ?>">
    <?php endif; ?>
</head>

<body class="bg-gray-100 overflow-x-hidden">


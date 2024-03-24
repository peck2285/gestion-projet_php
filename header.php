<?php
// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Récupère le nom d'utilisateur depuis la session
$username = $_SESSION['username'];
?>

<!-- header.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/admin.css"> <!-- Assurez-vous de lier vos styles ici -->
    <title>Tableau de bord</title>
</head>

<body>
    <header>
        <div class="header-left">
            <a href="projet.php">Gestion Projet</a>
        </div>
        <div class="header-center">
            <!-- <a href="projet.php">Projet</a> -->
            <!-- <a href="membre.php">Équipe</a> -->
            <!-- <a href="#">Tâches</a> -->
        </div>
        <div class="header-right">
            <span>Bienvenue, <?php echo $username; ?></span>
            <a href="deconnexion.php">Déconnexion</a>
        </div>
    </header>
    <main>
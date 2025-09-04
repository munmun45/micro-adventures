<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Micro Adventures</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Header with navigation -->
        
        <?php include './config/header.php'; ?>
        

        <!-- Main content area -->
        <main class="app-content">
            <!-- Hero section -->
            <div style="padding: 1rem;" >
            <section class="hero-section">
                <div class="hero-slider">
                    <div class="slider-container">
                        <div class="slide active" style="background-image: url('images/01.jpg');"></div>
                        <div class="slide" style="background-image: url('images/05.jpg');"></div>
                    </div>
                    <div class="slider-dots">
                        <span class="dot active" data-slide="0"></span>
                        <span class="dot" data-slide="1"></span>
                    </div>
                </div>
            </section>
            </div>

            <!-- Nearby Adventures -->
            <section class="nearby-adventures-section">
                <div class="section-header">
                    <h2>Nearby Adventures</h2>
                    <a href="#" class="view-all">View All</a>
                </div>
                <div class="nearby-adventures-container">
                    <!-- Adventure 1 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Nritya Kala</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.8</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹25,000</div>
                    </div>

                    <!-- Adventure 2 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Rhythm Masters</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.8</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹22,000</div>
                    </div>

                    <!-- Adventure 3 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Dance Fusion</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.7</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹20,000</div>
                    </div>

                    <!-- Adventure 4 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Classical Beats</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.9</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹28,000</div>
                    </div>

                    <!-- Adventure 5 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Melody Makers</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.6</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹19,500</div>
                    </div>

                    <!-- Adventure 6 -->
                    <div class="adventure-card">
                        <div class="adventure-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="adventure-details">
                            <h3>Taal Academy</h3>
                            <div class="adventure-rating">
                                <i class="fas fa-star"></i>
                                <span>4.7</span>
                            </div>
                        </div>
                        <div class="adventure-price">₹23,000</div>
                    </div>
                </div>
            </section>

            <!-- Adventure Categories -->
            <section class="adventure-categories-section" style="padding: 0 1rem 0;">
                <div class="section-header">
                    <h2>Adventure Categories</h2>
                    <a href="#" class="view-all">View All</a>
                </div>
                <div class="categories-container">
                    <!-- Category 1 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="category-name">Trekking</div>
                        <div class="category-count">12</div>
                    </div>

                    <!-- Category 2 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="category-name">Camping</div>
                        <div class="category-count">8</div>
                    </div>

                    <!-- Category 3 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="category-name">Rafting</div>
                        <div class="category-count">10</div>
                    </div>

                    <!-- Category 4 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="category-name">Rock Climbing</div>
                        <div class="category-count">6</div>
                    </div>

                    <!-- Category 5 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="category-name">Paragliding</div>
                        <div class="category-count">5</div>
                    </div>

                    <!-- Category 6 -->
                    <div class="category-card">
                        <div class="category-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="category-name">Kathakali</div>
                        <div class="category-count">7</div>
                    </div>
                </div>
            </section>

           

           

            <!-- Recommended Adventures -->
            <section class="recommended-section">
                <div class="section-header">
                    <h2>Recommended for You</h2>
                    <a href="#" class="view-all">View All</a>
                </div>
                <div class="adventure-list">
                    <!-- Recommended Adventure 1 -->
                    <div class="adventure-item">
                        <div class="item-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="item-content">
                            <h3>Morning Farmers Market</h3>
                            <p class="location">Central Square</p>
                            <p class="distance">0.8 miles away</p>
                        </div>
                        <button class="direction-btn"><i class="fas fa-directions"></i></button>
                    </div>

                    <!-- Recommended Adventure 2 -->
                    <div class="adventure-item">
                        <div class="item-image" style="background-image: url('images/05.jpg');"></div>
                        <div class="item-content">
                            <h3>Historic Walking Tour</h3>
                            <p class="location">Old Town</p>
                            <p class="distance">1.2 miles away</p>
                        </div>
                        <button class="direction-btn"><i class="fas fa-directions"></i></button>
                    </div>

                    <!-- Recommended Adventure 3 -->
                    <div class="adventure-item">
                        <div class="item-image" style="background-image: url('images/01.jpg');"></div>
                        <div class="item-content">
                            <h3>Rooftop Yoga Class</h3>
                            <p class="location">Skyline Building</p>
                            <p class="distance">1.5 miles away</p>
                        </div>
                        <button class="direction-btn"><i class="fas fa-directions"></i></button>
                    </div>
                </div>
            </section>
        </main>

        <?php include './config/bottom_navigation_bar.php'; ?>

        
    </div>

    <script src="js/script.js"></script>
</body>
</html>
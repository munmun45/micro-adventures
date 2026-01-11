-- Admin Panel Database Setup
CREATE DATABASE IF NOT EXISTS travelapp_admin;
USE travelapp_admin;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Insert Default Admin User (password: admin123)
-- Password is hashed using password_hash() - default password: admin123
INSERT INTO admin_users (username, email, password, full_name) VALUES
('admin', 'admin@travelapp.com', '$2y$10$br.E82xhl6fUpnmPkXNrV.qkhsklvkFYXH2lVH.K3SObHp1xqijeG', 'Administrator');

-- Sessions Table (for tracking admin sessions)
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- Activity Log Table
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- Countries Table
CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- States Table
CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    best_months TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
);

-- Cities Table (formerly Districts)
CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    road_type ENUM('plain','hill','mixed') DEFAULT 'plain',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
);

-- Activity Types Table
CREATE TABLE IF NOT EXISTS activity_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel Types Table
CREATE TABLE IF NOT EXISTS hotel_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transport Types Table
CREATE TABLE IF NOT EXISTS transport_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Places Table
CREATE TABLE IF NOT EXISTS places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    security_info TEXT,
    night_travel_safety TEXT,
    safety_level ENUM('High', 'Medium', 'Low', 'Worst') DEFAULT 'Medium',
    religion_restrictions TEXT,
    -- Visit Timing & Crowd Data
    best_time_of_day ENUM('morning','afternoon','evening','anytime'),
    avg_visit_time_hours DECIMAL(3,1),
    crowd_level ENUM('low','medium','high'),
    avg_waiting_time_minutes INT,
    -- Seasonal / Weather Risk
    weather_dependency ENUM('low','medium','high'),
    monsoon_risk ENUM('safe','risky','closed'),
    sudden_closure_risk ENUM('rare','occasional','frequent'),
    -- Family / Health Usability
    child_friendly ENUM('yes','no'),
    senior_friendly ENUM('yes','no'),
    physical_effort ENUM('low','medium','high'),
    -- Emergency Accessibility
    nearest_hospital_distance_km DECIMAL(5,2),
    police_response_level ENUM('good','medium','poor'),
    -- Network & Power Availability
    digital_amenities TEXT,
    mobile_network ENUM('good','weak','none'),
    internet_availability ENUM('yes','limited','no'),
    -- Place Priority & Experience Quality
    experience_score INT CHECK (experience_score BETWEEN 1 AND 5),
    priority_level INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES districts(id) ON DELETE CASCADE
);

-- Place Hotel Types Junction Table
CREATE TABLE IF NOT EXISTS place_hotel_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    hotel_type_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_type_id) REFERENCES hotel_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_place_hotel (place_id, hotel_type_id)
);

-- Place Transport Types Junction Table
CREATE TABLE IF NOT EXISTS place_transport_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    transport_type_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (transport_type_id) REFERENCES transport_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_place_transport (place_id, transport_type_id)
);

-- Place Activities Junction Table (with additional info)
CREATE TABLE IF NOT EXISTS place_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    activity_type_id INT NOT NULL,
    safety_info TEXT,
    safety_level ENUM('High', 'Medium', 'Low', 'Worst') DEFAULT 'Medium',
    age_restriction VARCHAR(50),
    gender_restriction VARCHAR(50),
    medical_restrictions TEXT,
    open_time TIME,
    close_time TIME,
    available_months TEXT,
    ticket_price VARCHAR(100),
    -- Activity Difficulty & Physical Load
    difficulty_level ENUM('easy','moderate','hard'),
    physical_effort ENUM('low','medium','high'),
    -- Weather & Season Risk
    weather_dependency ENUM('low','medium','high'),
    monsoon_risk ENUM('safe','risky','closed'),
    cancellation_chance ENUM('low','medium','high'),
    -- Safety Gear & Requirements
    safety_equipment_required ENUM('yes','no'),
    equipment_provided ENUM('yes','partial','no'),
    -- Supervision & Guide Quality
    guide_mandatory ENUM('yes','no'),
    operator_type ENUM('government','private','local'),
    -- Time Flexibility & Buffer
    buffer_time_minutes INT,
    reporting_time TIME,
    -- Crowd & Waiting Impact
    crowd_level ENUM('low','medium','high'),
    avg_waiting_time_minutes INT,
    -- Age & Body Limitation (More Precise)
    min_age INT,
    max_age INT,
    -- Medical Emergency Access
    nearest_medical_distance_km DECIMAL(5,2),
    emergency_support_level ENUM('good','medium','poor'),
    -- Activity Stop Conditions
    stop_conditions TEXT,
    -- Family & Solo Travel Suitability
    family_friendly ENUM('yes','no'),
    solo_traveler_safe ENUM('yes','no'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_type_id) REFERENCES activity_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_place_activity (place_id, activity_type_id)
);

-- Insert India as the only country
INSERT INTO countries (name, description, image) VALUES
('India', 'India is a diverse country with rich cultural heritage, beautiful landscapes, and amazing tourist destinations. From the snow-capped Himalayas to the tropical beaches, India offers a wide variety of experiences for travelers.', 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da');

-- Insert all Indian States
INSERT INTO states (country_id, name, description, image) VALUES
(1, 'Andhra Pradesh', 'Andhra Pradesh is known for its rich history, beautiful beaches, and delicious cuisine. It is home to famous temples and scenic hill stations.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
(1, 'Arunachal Pradesh', 'Arunachal Pradesh is a northeastern state known for its pristine natural beauty, diverse tribal culture, and Buddhist monasteries.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'Assam', 'Assam is famous for its tea gardens, wildlife sanctuaries, and the mighty Brahmaputra River. It is home to the one-horned rhinoceros.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2'),
(1, 'Bihar', 'Bihar is one of the oldest inhabited places in the world with rich historical and cultural heritage. It is the birthplace of Buddhism and Jainism.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Chhattisgarh', 'Chhattisgarh is known for its waterfalls, ancient caves, and tribal culture. It offers a unique blend of natural beauty and cultural heritage.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
(1, 'Goa', 'Goa is famous for its beautiful beaches, vibrant nightlife, and Portuguese-influenced architecture. It is a popular tourist destination.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'),
(1, 'Gujarat', 'Gujarat is known for its rich cultural heritage, vibrant festivals, and business-friendly environment. It is the birthplace of Mahatma Gandhi.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0'),
(1, 'Haryana', 'Haryana is known for its agricultural prosperity and rich history. It is home to several historical sites and monuments.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
(1, 'Himachal Pradesh', 'Himachal Pradesh is famous for its hill stations, snow-capped mountains, and adventure sports. It is a paradise for nature lovers.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'Jharkhand', 'Jharkhand is known for its mineral resources, waterfalls, and tribal culture. It offers a unique blend of natural beauty and industrial development.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
(1, 'Karnataka', 'Karnataka is known for its diverse landscapes, ancient temples, and modern IT hub Bangalore. It offers a perfect mix of tradition and modernity.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Kerala', 'Kerala is known as "God''s Own Country" for its backwaters, beaches, hill stations, and rich cultural heritage. It is a popular tourist destination.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'),
(1, 'Madhya Pradesh', 'Madhya Pradesh is known as the "Heart of India" and is famous for its historical monuments, wildlife sanctuaries, and cultural heritage.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2'),
(1, 'Maharashtra', 'Maharashtra is known for its vibrant culture, historical monuments, and economic capital Mumbai. It offers diverse experiences from beaches to hill stations.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
(1, 'Manipur', 'Manipur is known for its natural beauty, classical dance forms, and unique culture. It is often called the "Jewel of India".', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'Meghalaya', 'Meghalaya means "Abode of Clouds" and is known for its living root bridges, waterfalls, and beautiful landscapes. Shillong is its capital.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Mizoram', 'Mizoram is known for its scenic beauty, vibrant culture, and friendly people. It is one of the most peaceful states in India.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
(1, 'Nagaland', 'Nagaland is known for its rich tribal culture, beautiful landscapes, and vibrant festivals. It is a paradise for nature and culture enthusiasts.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'Odisha', 'Odisha is known for its ancient temples, beautiful beaches, and rich cultural heritage. Puri, Konark, and Bhubaneswar are major attractions.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Punjab', 'Punjab is known for its agricultural prosperity, vibrant culture, and delicious cuisine. It is the land of the five rivers.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0'),
(1, 'Rajasthan', 'Rajasthan is known for its royal heritage, magnificent forts, palaces, and desert landscapes. It is a major tourist destination.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0'),
(1, 'Sikkim', 'Sikkim is known for its breathtaking mountain views, Buddhist monasteries, and adventure tourism. It is a small but beautiful state.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'Tamil Nadu', 'Tamil Nadu is known for its ancient temples, classical dance forms, and beautiful beaches. It has a rich cultural and historical heritage.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Telangana', 'Telangana is known for its rich history, vibrant culture, and modern capital Hyderabad. It offers a blend of tradition and technology.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
(1, 'Tripura', 'Tripura is known for its beautiful palaces, temples, and natural beauty. It is a small state with rich cultural heritage.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
(1, 'Uttar Pradesh', 'Uttar Pradesh is known for its historical monuments, religious sites, and cultural heritage. It is home to the Taj Mahal.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
(1, 'Uttarakhand', 'Uttarakhand is known as "Devbhoomi" (Land of Gods) and is famous for its pilgrimage sites, hill stations, and adventure sports.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
(1, 'West Bengal', 'West Bengal is known for its rich cultural heritage, literature, art, and delicious cuisine. Kolkata is its cultural capital.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470');

-- Insert Sample Cities (a few examples for each state)
INSERT INTO districts (state_id, name, description, image, latitude, longitude, road_type) VALUES
-- Andhra Pradesh
(1, 'Visakhapatnam', 'Visakhapatnam, also known as Vizag, is a port city known for its beaches, hills, and industries.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 17.6868, 83.2185, 'plain'),
(1, 'Tirupati', 'Tirupati is famous for the Sri Venkateswara Temple, one of the most visited pilgrimage sites in India.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 13.6288, 79.4192, 'hill'),
-- Assam
(3, 'Guwahati', 'Guwahati is the largest city in Assam and gateway to Northeast India. It is known for Kamakhya Temple.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2', 26.1445, 91.7362, 'plain'),
(3, 'Kaziranga', 'Kaziranga is famous for the Kaziranga National Park, home to the one-horned rhinoceros.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2', 26.5739, 93.1715, 'plain'),
-- Goa
(6, 'North Goa', 'North Goa is famous for its vibrant beaches, nightlife, and water sports activities.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 15.5517, 73.7554, 'plain'),
(6, 'South Goa', 'South Goa offers serene beaches, heritage sites, and a more relaxed atmosphere.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 15.2993, 74.1240, 'plain'),
-- Karnataka
(11, 'Bangalore', 'Bangalore is the IT capital of India, known for its pleasant weather, parks, and modern infrastructure.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 12.9716, 77.5946, 'plain'),
(11, 'Mysore', 'Mysore is known for its royal heritage, palaces, and the famous Dasara festival.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 12.2958, 76.6394, 'plain'),
-- Kerala
(12, 'Kochi', 'Kochi is a port city known for its backwaters, Chinese fishing nets, and colonial architecture.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 9.9312, 76.2673, 'plain'),
(12, 'Munnar', 'Munnar is a hill station famous for its tea plantations, scenic beauty, and cool climate.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 10.0889, 77.0595, 'hill'),
-- Maharashtra
(14, 'Mumbai', 'Mumbai is the financial capital of India, known for Bollywood, Gateway of India, and vibrant culture.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 19.0760, 72.8777, 'plain'),
(14, 'Pune', 'Pune is known as the cultural capital of Maharashtra, famous for its educational institutions and historical sites.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 18.5204, 73.8567, 'plain'),
-- Odisha
(19, 'Puri', 'Puri is famous for the Jagannath Temple, beautiful beaches, and is one of the Char Dham pilgrimage sites.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 19.8135, 85.8312, 'plain'),
(19, 'Bhubaneswar', 'Bhubaneswar is the capital of Odisha, known as the Temple City with numerous ancient temples.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2', 20.2961, 85.8245, 'plain'),
-- Rajasthan
(21, 'Jaipur', 'Jaipur, the Pink City, is known for its magnificent palaces, forts, and rich royal heritage.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', 26.9124, 75.7873, 'plain'),
(21, 'Udaipur', 'Udaipur, the City of Lakes, is famous for its beautiful lakes, palaces, and romantic ambiance.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', 24.5854, 73.7125, 'plain'),
-- Tamil Nadu
(23, 'Chennai', 'Chennai is the capital of Tamil Nadu, known for its beaches, temples, and classical music.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 13.0827, 80.2707, 'plain'),
(23, 'Madurai', 'Madurai is famous for the Meenakshi Amman Temple, one of the most important temples in South India.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 9.9252, 78.1198, 'plain'),
-- Uttar Pradesh
(26, 'Agra', 'Agra is home to the iconic Taj Mahal, one of the Seven Wonders of the World.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 27.1767, 78.0081, 'plain'),
(26, 'Varanasi', 'Varanasi is one of the oldest cities in the world, known for its ghats, temples, and spiritual significance.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 25.3176, 82.9739, 'plain'),
-- West Bengal
(28, 'Kolkata', 'Kolkata is the cultural capital of India, known for its literature, art, colonial architecture, and delicious food.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 22.5726, 88.3639, 'plain'),
(28, 'Darjeeling', 'Darjeeling is a famous hill station known for its tea plantations, toy train, and stunning views of Kanchenjunga.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 27.0360, 88.2627, 'hill');

-- Insert Activity Types
INSERT INTO activity_types (name, description, image) VALUES
('Trekking', 'Explore mountains, hills, and forests through exciting trekking adventures. Perfect for adventure enthusiasts who love hiking and nature.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
('Boating', 'Enjoy peaceful boat rides on rivers, lakes, and backwaters. Experience the tranquility of water bodies and scenic surroundings.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
('Camping', 'Spend nights under the stars with camping adventures. Connect with nature and enjoy outdoor activities in beautiful locations.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
('Beach Activities', 'Enjoy various beach activities including swimming, sunbathing, beach volleyball, and water sports at beautiful coastal destinations.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'),
('Wildlife Safari', 'Experience wildlife in their natural habitat through exciting safari tours. Spot various animals and birds in national parks and sanctuaries.', 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2'),
('Temple Tours', 'Visit ancient and modern temples to explore religious heritage, architecture, and spiritual experiences.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
('Heritage Walks', 'Explore historical sites, monuments, and heritage buildings through guided walking tours. Learn about rich cultural history.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0'),
('Photography Tours', 'Capture beautiful landscapes, wildlife, and cultural moments through specialized photography tours for enthusiasts.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
('Adventure Sports', 'Experience thrilling adventure sports including paragliding, rock climbing, river rafting, and bungee jumping.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
('Cultural Shows', 'Enjoy traditional dance performances, music concerts, and cultural shows showcasing local art and traditions.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
('Food Tours', 'Explore local cuisine and street food through guided food tours. Taste authentic regional dishes and delicacies.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'),
('Shopping Tours', 'Discover local markets, handicrafts, and traditional items through shopping tours in various destinations.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e');

-- Insert Hotel Types
INSERT INTO hotel_types (name, description, image) VALUES
('Luxury Hotels', 'Experience world-class luxury with premium amenities, fine dining, spa services, and exceptional hospitality in luxury hotels.', 'https://images.unsplash.com/photo-1566073771259-6a8506099945'),
('Budget Hotels', 'Affordable accommodation options with essential amenities, perfect for budget-conscious travelers seeking comfort and value.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427'),
('Resort Hotels', 'Relax in beautiful resort hotels with pools, gardens, recreational facilities, and scenic locations for a perfect vacation.', 'https://images.unsplash.com/photo-1564501049412-61c2a3083791'),
('Boutique Hotels', 'Stay in unique boutique hotels offering personalized service, distinctive design, and intimate atmosphere.', 'https://images.unsplash.com/photo-1566073771259-6a8506099945'),
('Heritage Hotels', 'Experience royal heritage and history by staying in converted palaces, forts, and historical buildings.', 'https://images.unsplash.com/photo-1548013146-72479768bada'),
('Beach Resorts', 'Enjoy beachfront accommodation with direct access to beaches, water sports, and stunning ocean views.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429'),
('Hill Station Hotels', 'Stay in hotels located in beautiful hill stations offering cool climate, mountain views, and peaceful surroundings.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'),
('Eco-Friendly Hotels', 'Stay in environmentally conscious hotels that focus on sustainability, nature conservation, and eco-friendly practices.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
('Business Hotels', 'Modern hotels designed for business travelers with conference facilities, high-speed internet, and convenient locations.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427'),
('Homestays', 'Experience local culture and hospitality by staying with local families in comfortable homestay accommodations.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
('Backpacker Hostels', 'Budget-friendly hostels perfect for solo travelers and backpackers, offering shared accommodations and social atmosphere.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
('Spa & Wellness Hotels', 'Rejuvenate in hotels offering spa treatments, yoga sessions, wellness programs, and holistic health facilities.', 'https://images.unsplash.com/photo-1566073771259-6a8506099945');

-- Insert Transport Types
INSERT INTO transport_types (name, description, image) VALUES
('Bus', 'Comfortable and affordable bus travel for short and long distances. Perfect for budget-conscious travelers exploring cities and towns.', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957'),
('Car', 'Private car rentals for flexible and convenient travel. Explore destinations at your own pace with full control over your itinerary.', 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7'),
('Bike', 'Motorcycle and scooter rentals for adventurous travelers. Experience the freedom of two-wheeled travel and explore off-the-beaten-path destinations.', 'https://images.unsplash.com/photo-1558980663-3685c1d673c8'),
('Train', 'Scenic train journeys connecting major cities and destinations. Enjoy comfortable travel with beautiful landscapes passing by your window.', 'https://images.unsplash.com/photo-1557223562-6c77ef16281f'),
('Flight', 'Fast and efficient air travel for long distances. Reach your destination quickly with domestic and international flight options.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05'),
('Private Jet', 'Luxury private jet charters for exclusive and personalized travel experiences. Enjoy premium comfort, privacy, and flexibility.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05'),
('Taxi', 'Convenient taxi services for quick city transfers and local transportation. Reliable and readily available for your travel needs.', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957'),
('Auto Rickshaw', 'Traditional three-wheeler auto rickshaws for short-distance travel. Experience local transportation in an authentic way.', 'https://images.unsplash.com/photo-1558980663-3685c1d673c8'),
('Boat', 'Water transport including ferries, boats, and cruises. Perfect for island hopping, river crossings, and scenic water journeys.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
('Helicopter', 'Helicopter tours and transfers for unique aerial views and quick access to remote locations. Experience breathtaking landscapes from above.', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05');

-- Insert Sample Places
INSERT INTO places (city_id, name, description, image, security_info, night_travel_safety, safety_level, religion_restrictions, best_time_of_day, avg_visit_time_hours, crowd_level, avg_waiting_time_minutes, weather_dependency, monsoon_risk, sudden_closure_risk, child_friendly, senior_friendly, physical_effort, nearest_hospital_distance_km, police_response_level, digital_amenities, mobile_network, internet_availability, experience_score, priority_level) VALUES
-- Mumbai Places
(9, 'Gateway of India', 'The Gateway of India is an arch monument built during the 20th century in Mumbai. It is one of the most iconic landmarks of the city and a popular tourist attraction.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 'Generally safe area with police presence. Be cautious of pickpockets in crowded areas. Keep valuables secure.', 'Moderately safe for night travel. Well-lit area with security personnel. Avoid isolated areas after midnight.', 'High', 'No Restriction', 'anytime', 1.5, 'high', 15, 'low', 'safe', 'rare', 'yes', 'yes', 'low', 2.5, 'good', 'Free WiFi available nearby. Good mobile network coverage. Digital payment accepted at most vendors.', 'good', 'yes', 5, 5),
(9, 'Marine Drive', 'Marine Drive is a 3.6-kilometer-long boulevard in South Mumbai. It is also known as the Queen''s Necklace due to its curved shape and street lights that resemble a string of pearls.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 'Safe area with regular police patrolling. Popular among locals and tourists. Keep belongings close.', 'Safe for night travel. Well-lit promenade. Many people visit during evenings. Exercise caution after 11 PM.', 'High', 'No Restriction', 'evening', 2.0, 'medium', 5, 'low', 'safe', 'rare', 'yes', 'yes', 'low', 3.0, 'good', 'Good mobile network. Some cafes offer WiFi. Digital payment widely accepted.', 'good', 'yes', 4, 4),
-- Agra Places
(19, 'Taj Mahal', 'The Taj Mahal is an ivory-white marble mausoleum on the right bank of the Yamuna river in Agra. It was commissioned in 1632 by the Mughal emperor Shah Jahan to house the tomb of his favorite wife, Mumtaz Mahal.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 'Highly secure area with strict security checks. Bags and certain items not allowed inside. Follow security guidelines.', 'Not accessible at night. Open from sunrise to sunset. Night viewing available on full moon nights with special tickets.', 'High', 'No Restriction', 'morning', 3.0, 'high', 60, 'low', 'safe', 'rare', 'yes', 'yes', 'low', 5.0, 'good', 'Limited WiFi. Good mobile network. Online ticket booking available. Digital payment accepted.', 'good', 'limited', 5, 5),
(19, 'Agra Fort', 'Agra Fort is a historical fort in the city of Agra in India. It was the main residence of the emperors of the Mughal Dynasty until 1638, when the capital was shifted from Agra to Delhi.', 'https://images.unsplash.com/photo-1548013146-72479768bada', 'Secure monument with security personnel. Follow rules and regulations. Keep valuables safe.', 'Open during daylight hours only. Not recommended for night visits.', 'High', 'No Restriction', 'morning', 2.5, 'medium', 30, 'low', 'safe', 'rare', 'yes', 'yes', 'medium', 4.5, 'good', 'Good mobile network coverage. Online ticket booking available.', 'good', 'yes', 4, 4),
-- Jaipur Places
(13, 'Hawa Mahal', 'Hawa Mahal, also known as the Palace of Winds, is a five-story building in Jaipur. It was built in 1799 by Maharaja Sawai Pratap Singh and is one of the most iconic landmarks of Jaipur.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', 'Safe tourist area with security. Be cautious of street vendors and touts. Keep belongings secure.', 'Moderately safe. Well-lit main area. Avoid isolated streets after dark.', 'Medium', 'No Restriction', 'morning', 1.5, 'medium', 20, 'low', 'safe', 'rare', 'yes', 'no', 'medium', 2.0, 'good', 'Good mobile network. Some shops offer WiFi. Digital payment accepted.', 'good', 'yes', 4, 3),
(13, 'Amber Fort', 'Amber Fort is a massive fort located in Amer, about 11 kilometers from Jaipur. It is one of the principal tourist attractions in the Jaipur area, located high on a hill.', 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0', 'Secure fort area with security personnel. Follow safety guidelines. Keep valuables safe.', 'Open during daylight hours. Not recommended for night visits. Limited lighting in some areas.', 'Medium', 'No Restriction', 'morning', 3.0, 'medium', 45, 'low', 'safe', 'rare', 'yes', 'no', 'high', 6.0, 'good', 'Good mobile network. Online ticket booking available. Digital payment accepted.', 'good', 'yes', 5, 5),
-- Goa Places
(5, 'Baga Beach', 'Baga Beach is one of the most popular beaches in North Goa, known for its water sports, nightlife, and vibrant atmosphere. It offers a perfect blend of relaxation and adventure.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 'Generally safe beach area. Be cautious in water. Follow lifeguard instructions. Keep valuables in lockers.', 'Moderately safe. Well-lit beach shacks. Many tourists visit at night. Exercise caution on isolated beach areas.', 'Medium', 'No Restriction', 'anytime', 4.0, 'high', 10, 'medium', 'risky', 'occasional', 'yes', 'yes', 'low', 8.0, 'medium', 'Good mobile network. Many beach shacks offer WiFi. Digital payment widely accepted.', 'good', 'yes', 4, 4),
(5, 'Calangute Beach', 'Calangute Beach is the largest and most commercialized beach in North Goa. It is known for its golden sand, water sports, and numerous restaurants and shops.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 'Safe tourist area. Be cautious in water. Follow safety guidelines for water sports.', 'Safe for evening visits. Well-lit area with many restaurants and shops open late.', 'High', 'No Restriction', 'anytime', 4.0, 'high', 15, 'medium', 'risky', 'occasional', 'yes', 'yes', 'low', 7.5, 'good', 'Excellent mobile network. Many establishments offer WiFi. Digital payment widely accepted.', 'good', 'yes', 4, 4),
-- Kerala Places
(7, 'Backwaters of Alleppey', 'The backwaters of Alleppey are a network of canals, rivers, and lakes that form a unique ecosystem. Houseboat cruises are the most popular way to experience this natural wonder.', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', 'Safe waterway area. Follow safety instructions on houseboats. Wear life jackets when required.', 'Limited night travel on backwaters. Most houseboats dock at night. Safe when on anchored houseboats.', 'High', 'No Restriction', 'morning', 24.0, 'low', 0, 'high', 'risky', 'occasional', 'yes', 'yes', 'low', 12.0, 'medium', 'Limited mobile network in some areas. Some houseboats offer WiFi. Digital payment accepted for bookings.', 'weak', 'limited', 5, 5),
(8, 'Munnar Tea Gardens', 'Munnar is famous for its sprawling tea plantations that cover the hillsides. The tea gardens offer breathtaking views and opportunities to learn about tea processing.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 'Safe plantation areas. Follow guided tour instructions. Be cautious on steep paths.', 'Not recommended for night visits. Tea gardens close at sunset. Limited lighting in plantation areas.', 'Medium', 'No Restriction', 'morning', 3.0, 'low', 10, 'medium', 'risky', 'rare', 'yes', 'no', 'medium', 15.0, 'medium', 'Good mobile network in main areas. Some resorts offer WiFi. Digital payment accepted.', 'good', 'limited', 4, 4),
-- Darjeeling Places
(20, 'Tiger Hill', 'Tiger Hill is famous for its spectacular sunrise views over Mount Kanchenjunga and Mount Everest. It is one of the highest points in Darjeeling and a popular tourist destination.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 'Safe viewing area. Be cautious on steep paths, especially in early morning darkness. Follow guide instructions.', 'Early morning visits for sunrise are safe with proper lighting. Avoid isolated areas in complete darkness.', 'Medium', 'No Restriction', 'morning', 2.0, 'low', 30, 'high', 'closed', 'frequent', 'yes', 'no', 'medium', 18.0, 'medium', 'Good mobile network at viewing point. Limited WiFi. Digital payment accepted for entry tickets.', 'weak', 'limited', 5, 5),
(20, 'Darjeeling Himalayan Railway', 'The Darjeeling Himalayan Railway, also known as the Toy Train, is a narrow-gauge railway that runs between New Jalpaiguri and Darjeeling. It is a UNESCO World Heritage Site.', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 'Safe railway journey. Follow safety instructions. Keep belongings secure during travel.', 'Limited night services. Most trains operate during daylight hours. Safe when traveling in groups.', 'High', 'No Restriction', 'morning', 7.0, 'medium', 20, 'medium', 'risky', 'occasional', 'yes', 'yes', 'low', 10.0, 'good', 'Good mobile network along most routes. Online ticket booking available. Digital payment accepted.', 'good', 'yes', 5, 4);

-- Insert Place Hotel Types (Sample associations)
INSERT INTO place_hotel_types (place_id, hotel_type_id) VALUES
-- Gateway of India, Mumbai
(1, 1), (1, 2), (1, 9), (1, 11),
-- Marine Drive, Mumbai
(2, 1), (2, 2), (2, 9), (2, 12),
-- Taj Mahal, Agra
(3, 1), (3, 4), (3, 5), (3, 9),
-- Agra Fort, Agra
(4, 2), (4, 5), (4, 9), (4, 10),
-- Hawa Mahal, Jaipur
(5, 1), (5, 4), (5, 5), (5, 9),
-- Amber Fort, Jaipur
(6, 1), (6, 5), (6, 7), (6, 9),
-- Baga Beach, Goa
(7, 3), (7, 6), (7, 2), (7, 11),
-- Calangute Beach, Goa
(8, 3), (8, 6), (8, 2), (8, 12),
-- Backwaters of Alleppey, Kerala
(9, 3), (9, 8), (9, 10), (9, 12),
-- Munnar Tea Gardens, Kerala
(10, 7), (10, 8), (10, 2), (10, 12),
-- Tiger Hill, Darjeeling
(11, 7), (11, 2), (11, 10), (11, 12),
-- Darjeeling Himalayan Railway, Darjeeling
(12, 7), (12, 2), (12, 10), (12, 12);

-- Insert Place Transport Types (Sample associations)
INSERT INTO place_transport_types (place_id, transport_type_id) VALUES
-- Gateway of India, Mumbai
(1, 2), (1, 7), (1, 8), (1, 4),
-- Marine Drive, Mumbai
(2, 2), (2, 7), (2, 8), (2, 4),
-- Taj Mahal, Agra
(3, 2), (3, 7), (3, 4), (3, 1),
-- Agra Fort, Agra
(4, 2), (4, 7), (4, 4), (4, 1),
-- Hawa Mahal, Jaipur
(5, 2), (5, 7), (5, 3), (5, 4),
-- Amber Fort, Jaipur
(6, 2), (6, 7), (6, 3), (6, 4),
-- Baga Beach, Goa
(7, 2), (7, 3), (7, 7), (7, 9),
-- Calangute Beach, Goa
(8, 2), (8, 3), (8, 7), (8, 9),
-- Backwaters of Alleppey, Kerala
(9, 2), (9, 7), (9, 9), (9, 4),
-- Munnar Tea Gardens, Kerala
(10, 2), (10, 7), (10, 4), (10, 1),
-- Tiger Hill, Darjeeling
(11, 2), (11, 7), (11, 4), (11, 1),
-- Darjeeling Himalayan Railway, Darjeeling
(12, 4), (12, 2), (12, 7), (12, 1);

-- Insert Place Activities (Sample associations with safety info)
INSERT INTO place_activities (place_id, activity_type_id, safety_info, safety_level, age_restriction, gender_restriction, medical_restrictions, open_time, close_time, available_months, ticket_price) VALUES
-- Gateway of India - Heritage Walks
(1, 7, 'Follow guide instructions. Stay with group. Be cautious of traffic when crossing roads.', 'High', 'All ages', 'No restriction', 'Heart conditions, mobility issues', '09:00:00', '18:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', 'Free'),
-- Gateway of India - Photography Tours
(1, 8, 'Respect other visitors. No tripods in restricted areas. Keep equipment secure.', 'High', 'All ages', 'No restriction', 'None', '06:00:00', '20:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', 'Free'),
-- Marine Drive - Beach Activities
(2, 4, 'Swimming not recommended due to strong currents. Enjoy walking and sightseeing. Be cautious near water.', 'Medium', 'All ages', 'No restriction', 'Heart conditions, respiratory problems, epilepsy', '05:00:00', '22:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', 'Free'),
-- Taj Mahal - Temple Tours
(3, 6, 'Follow security guidelines. No food or smoking inside. Respect the monument. Wear appropriate clothing.', 'High', 'All ages', 'No restriction', 'None', '06:00:00', '19:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', '50-200'),
-- Taj Mahal - Photography Tours
(3, 8, 'Photography allowed in designated areas. No tripods or professional equipment without permission. Respect other visitors.', 'High', 'All ages', 'No restriction', 'None', '06:00:00', '19:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', '50-200'),
-- Agra Fort - Heritage Walks
(4, 7, 'Wear comfortable shoes. Stay on marked paths. Be cautious on stairs and uneven surfaces.', 'Medium', 'All ages', 'No restriction', 'Mobility issues, knee problems', '06:00:00', '18:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', '40-100'),
-- Hawa Mahal - Heritage Walks
(5, 7, 'Narrow passages require caution. Follow one-way routes. Be careful on stairs.', 'Medium', 'All ages', 'No restriction', 'Claustrophobia, mobility issues', '09:00:00', '17:00:00', 'January,February,March,April,May,June,July,August,September,October,November,December', '50'),
-- Amber Fort - Heritage Walks
(6, 7, 'Steep paths require good physical condition. Wear comfortable shoes. Stay hydrated.', 'Low', '10+ years', 'No restriction', 'Heart conditions, respiratory problems, mobility issues', '08:00:00', '18:00:00', 'October,November,December,January,February,March', '100-200'),
-- Amber Fort - Adventure Sports
(6, 9, 'Elephant rides available with safety measures. Follow guide instructions. Check equipment before use.', 'Medium', '8+ years', 'No restriction', 'Back problems, pregnancy, heart conditions', '08:00:00', '17:00:00', 'October,November,December,January,February,March', '500-1000'),
-- Baga Beach - Beach Activities
(7, 4, 'Swim only in designated areas with lifeguards. Follow water sports safety guidelines. Use sunscreen.', 'Medium', 'All ages', 'No restriction', 'Heart conditions, epilepsy, respiratory problems', '06:00:00', '20:00:00', 'November,December,January,February,March,April,May', 'Free'),
-- Baga Beach - Adventure Sports
(7, 9, 'Parasailing, jet skiing available. Follow instructor guidelines. Check equipment safety. Wear life jackets.', 'Low', '12+ years', 'No restriction', 'Heart conditions, back problems, pregnancy, epilepsy', '08:00:00', '18:00:00', 'November,December,January,February,March,April,May', '800-2000'),
-- Calangute Beach - Beach Activities
(8, 4, 'Swim in designated safe zones. Follow lifeguard instructions. Be cautious of strong currents.', 'Medium', 'All ages', 'No restriction', 'Heart conditions, respiratory problems', '06:00:00', '20:00:00', 'November,December,January,February,March,April,May', 'Free'),
-- Backwaters of Alleppey - Boating
(9, 2, 'Wear life jackets on houseboats. Follow captain instructions. Be cautious when boarding/disembarking.', 'High', 'All ages', 'No restriction', 'Motion sickness, severe balance issues', '08:00:00', '18:00:00', 'June,July,August,September,October,November,December,January,February,March', '2000-5000'),
-- Backwaters of Alleppey - Camping
(9, 3, 'Camping on houseboats is safe. Follow safety guidelines. Keep fire safety in mind.', 'High', 'All ages', 'No restriction', 'Severe allergies, respiratory problems', '18:00:00', '08:00:00', 'June,July,August,September,October,November,December,January,February,March', '1500-3000'),
-- Munnar Tea Gardens - Trekking
(10, 1, 'Moderate difficulty trails. Wear appropriate footwear. Stay with guide. Be cautious of wildlife.', 'Low', '12+ years', 'No restriction', 'Heart conditions, respiratory problems, knee/back problems, asthma', '06:00:00', '17:00:00', 'September,October,November,December,January,February,March,April,May', '300-800'),
-- Munnar Tea Gardens - Photography Tours
(10, 8, 'Respect private property. Follow guide instructions. Be cautious on uneven terrain.', 'High', 'All ages', 'No restriction', 'Mobility issues', '06:00:00', '18:00:00', 'September,October,November,December,January,February,March,April,May', '200-500'),
-- Tiger Hill - Photography Tours
(11, 8, 'Early morning visits require warm clothing. Be cautious in darkness. Follow guide instructions.', 'High', 'All ages', 'No restriction', 'Respiratory problems in cold weather', '04:00:00', '08:00:00', 'October,November,December,January,February,March,April', '100-300'),
-- Darjeeling Himalayan Railway - Heritage Walks
(12, 7, 'Be cautious when boarding trains. Hold handrails. Follow railway safety guidelines.', 'High', 'All ages', 'No restriction', 'Mobility issues, severe balance problems', '08:00:00', '17:00:00', 'March,April,May,June,July,August,September,October,November', '50-200');

-- Planning Rules Table
CREATE TABLE IF NOT EXISTS planning_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    same_stay_hour_limit INT DEFAULT 4,
    travel_day_hour_limit INT DEFAULT 7,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default planning rules
INSERT INTO planning_rules (same_stay_hour_limit, travel_day_hour_limit) VALUES (4, 7);


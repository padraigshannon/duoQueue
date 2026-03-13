CREATE DATABASE IF NOT EXISTS myproject_db;
USE myproject_db;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(100),
    is_admin BOOLEAN,
    is_banned BOOLEAN,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_profiles (
    user_id INT PRIMARY KEY,
    location VARCHAR(255),
    profile_photo VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    seeking ENUM('Male', 'Female', 'Other'),
    about_me TEXT,
    smoker BOOLEAN,
    drinker BOOLEAN,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE user_photos (
    user_id INT,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE available_genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(255)
);

CREATE TABLE available_games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(255)
);

CREATE TABLE game_genres (
    game_id INT,
    genre_id INT,
    PRIMARY KEY (game_id, genre_id),
    FOREIGN KEY (game_id) REFERENCES available_games(game_id),
    FOREIGN KEY (genre_id) REFERENCES available_genres(genre_id)
);

CREATE TABLE users_games (
    user_id INT,
    game_id INT,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (game_id) REFERENCES available_games(game_id)
);

CREATE TABLE available_platforms (
    platform_id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(255)
);

CREATE TABLE user_platforms (
    user_id INT,
    platform_id INT,
    platform_username VARCHAR(255),
    PRIMARY KEY (user_id, platform_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (platform_id) REFERENCES available_platforms(platform_id)
);

CREATE TABLE like (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    liked_user_id INT,
    status ENUM('PROCESSING', 'MATCHED', 'REJECTED'),
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (user_id, liked_user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (liked_user_id) REFERENCES users(user_id),
);

CREATE TABLE dislike (
    dislike_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    disliked_user_id INT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (user_id, liked_user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (disliked_user_id) REFERENCES users(user_id),
);

CREATE TABLE match (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT,
    user2_id INT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(user_id),
    FOREIGN KEY (user2_id) REFERENCES users(user_id),
);

CREATE TABLE message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT,
    message TEXT,
    sender_id INT,
    receiver_id INT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- TO BE CONTINUED!!!!
)

CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporting_user_id INT,
    reported_user_id INT,
    reason TEXT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporting_user_id) REFERENCES users(user_id),
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id)
);

CREATE TABLE banned (
    user_id INT PRIMARY KEY,
    admin_id INT,
    reason TEXT,
    ban_duration INT,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
)
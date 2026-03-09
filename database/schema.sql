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
    profile_photo VARCHAR(255)
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
    photo VARCHAR(255)
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE available_genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(255)
);

CREATE TABLE available_games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(255)
)

CREATE TABLE game_genres (
    game_id INT,
    genre_id INT,
    PRIMARY KEY (game_id, genre_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id)
)

CREATE TABLE users_games
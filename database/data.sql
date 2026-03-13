-- Inserts for the tables (Test data)
INSERT INTO users (first_name, last_name, email, password, is_admin, is_banned) 
VALUES
('John', 'Doe', 'jDizzle@gmail.com', 'password123', false, false),
('Jane', 'Smith', 'janesmail@gmail.com', 'janespassword', false, false)
ON DUPLICATE KEY UPDATE
email=email;

INSERT INTO user_profiles (user_id, location, profile_photo, date_of_birth, gender, seeking, about_me, smoker, drinker)
VALUES
(1, 'Gorey', '', '2000-01-01', 'Male', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit.
 Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis.
 Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas.
 Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu.
 Ad litora torquent per conubia nostra inceptos himenaeos.', false, true),

(2, 'Feakle', '', '1999-12-3', 'Female', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit.
 Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis.
 Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas.
 Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu.
 Ad litora torquent per conubia nostra inceptos himenaeos.', true, false)
ON DUPLICATE KEY UPDATE
user_id=user_id;
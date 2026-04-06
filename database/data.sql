-- Inserts for the tables (Test data)
INSERT INTO users (first_name, last_name, email, password, is_admin, is_banned) 
VALUES
('John', 'Doe', 'jDizzle@gmail.com', 'password123', false, false),
('Jane', 'Smith', 'janesmail@gmail.com', 'janespassword', false, false),
('Alex', 'Johnson', 'alex.j@gmail.com', 'pass456', false, false),
('Sarah', 'Williams', 'sarah.w@gmail.com', 'pass789', false, false),
('Mike', 'Brown', 'mike.b@gmail.com', 'pass101', false, false),
('Emma', 'Davis', 'emma.d@gmail.com', 'pass202', false, false),
('Chris', 'Miller', 'chris.m@gmail.com', 'pass303', false, false),
('Lisa', 'Wilson', 'lisa.w@gmail.com', 'pass404', false, false),
('Tom', 'Moore', 'tom.m@gmail.com', 'pass505', false, false),
('Jessica', 'Taylor', 'jessica.t@gmail.com', 'pass606', false, false),
('David', 'Anderson', 'david.a@gmail.com', 'pass707', false, false),
('Amy', 'Thomas', 'amy.t@gmail.com', 'pass808', false, false)
ON DUPLICATE KEY UPDATE
email=email;

INSERT INTO user_profiles (user_id, location, profile_photo, date_of_birth, gender, seeking, about_me, smoker, drinker)
VALUES
(1, 'Gorey', '', '2000-01-01', 'Male', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', false, true),
(2, 'Feakle', '', '1999-12-3', 'Female', 'Female', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', true, false),
(3, 'Dublin', '', '1998-05-15', 'Male', 'Female', 'Passionate gamer looking for my duo! I love competitive games and strategy RPGs. Always up for new gaming friends and adventures in the digital world.', false, false),
(4, 'Cork', '', '2001-08-22', 'Female', 'Male', 'Casual player who loves cozy games and story-driven adventures. Looking for someone chill to game with and maybe grab a drink after.', false, true),
(5, 'Limerick', '', '1997-03-10', 'Male', 'Male', 'Hardcore FPS player. Competitive, dedicated, and always grinding. Looking for a serious gaming partner who can keep up with my skill level.', true, true),
(6, 'Waterford', '', '2000-11-07', 'Female', 'Female', 'Indie game enthusiast and speedrunner. Love challenging myself and discovering hidden gems. Would love to find a fellow speedrunner!', false, false),
(7, 'Galway', '', '1996-07-19', 'Male', 'Female', 'Retro gaming collector and speedrunner. Classic games are my passion. If you appreciate good gameplay and nostalgia, hit me up!', false, true),
(8, 'Belfast', '', '2002-01-30', 'Female', 'Other', 'MMO addict looking for raid buddies! I play daily and am looking for a committed teammate. Voice chat essential!', false, false),
(9, 'Derry', '', '1999-09-14', 'Male', 'Other', 'Open-minded gamer interested in all genres. Mostly play at night. Looking for chill people to have fun with, no pressure gaming.', true, false),
(10, 'Sligo', '', '2000-04-28', 'Female', 'Female', 'Overwatch and Valorant player. Competitive but fun-loving. Would love to find teammates who are passionate about improving together.', false, true),
(11, 'Droichead Átha', '', '1998-12-05', 'Male', 'Female', 'Story-driven game lover. I value good narratives and character development. Looking for someone to discuss games philosophically with!', false, false),
(12, 'Kilkenny', '', '2001-06-17', 'Female', 'Male', 'Casual mobile and Switch player. Looking for laid-back gaming sessions. No toxicity, just good vibes and fun times!', true, true)
ON DUPLICATE KEY UPDATE
user_id=user_id;
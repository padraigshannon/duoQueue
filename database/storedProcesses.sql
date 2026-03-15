DELIMITER $$

CREATE PROCEDURE GetMatchMessages(
    IN p_match_id INT,
    IN p_limit INT,
    IN p_offset INT
)

BEGIN
    SELECT 
        m.message_id,
        m.message,
        m.created_timestamp,
        m.sender_id,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        m.receiver_id
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.match_id = p_match_id
    ORDER BY m.created_timestamp ASC
    LIMIT p_limit OFFSET p_offset;

END$$

CREATE PROCEDURE GetUserProfile(
    IN p_user_id INT
)

BEGIN
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.created_timestamp,
        up.location,
        up.profile_photo,
        up.date_of_birth,
        up.gender,
        up.seeking,
        up.about_me,
        up.smoker,
        up.drinker
    FROM users u
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = p_user_id;

    SELECT photo
    FROM user_photos
    WHERE user_id = p_user_id;

    SELECT ag.game_id, ag.game_name
    FROM users_games ug
    JOIN available_games ag ON ug.game_id = ag.game_id
    WHERE ug.user_id = p_user_id;

    SELECT ap.platform_name, upl.platform_username
    FROM user_platforms upl
    JOIN available_platforms ap ON upl.platform_id = ap.platform_id
    WHERE upl.user_id = p_user_id;

END$$

CREATE PROCEDURE GetSharedGames(
    IN p_user1_id INT,
    IN p_user2_id INT
)
BEGIN
    SELECT ag.game_id, ag.game_name
    FROM users_games u1
    JOIN users_games u2 ON u1.game_id = u2.game_id
    JOIN available_games ag ON u1.game_id = ag.game_id
    WHERE u1.user_id = p_user1_id
      AND u2.user_id = p_user2_id;
END$$

DELIMITER ;

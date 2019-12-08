CREATE EVENT deleteExpiredRefreshTokens ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE
DO
   	DELETE FROM refresh_tokens WHERE expires < NOW();

CREATE EVENT deleteExpiredAccessTokens ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE
DO
	DELETE FROM access_tokens WHERE expires < NOW();

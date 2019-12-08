CREATE EVENT deleteExpiredTokens ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE
DO
	DELETE FROM authorization_codes WHERE expires < NOW();

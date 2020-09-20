<?php

class web {
	
	public static function login_userId() {
		return self::session("id", -1);
	}

	public static function login_userName() {
		return self::session("realname", "未登录");
	}

	public static function login_userRole() {
		return self::session("role", "普通收银员");
	}

	public static function login_userMail() {
		return self::session("email", "未登录");
	}

	public static function session($name, $default = null) {
		if (array_key_exists($name, $_SESSION) && !empty($_SESSION[$name])) {
			return $_SESSION[$name];
		} else {
			return $default;
		}
	}
}
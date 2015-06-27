<?php namespace Cloudoki\Guardian;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Input;

class Guardian
{	
	/**
	 *	Allowed
	 *	Is the user allowed? Valid access token,
	 *	account relation and user roles can be checked
	 *
	 *	@param	int		$accountid
	 *	@param	array	$roles
	 *	@return boolean
	 */
	public static function allowed ($accountid = null, $roles = array())
	{
		$token = Input::get ('access_token');
		
		return !
		(
			// Is the acces token valid?
			!self::validAccess ($token) ||
			
			// Is the user and account connected?
			($accountid && !self::accountRelation ($token, $accountid)) ||
			
			// Has the user the required roles for the account?
			($accountid && count($roles) && !self::hasRoles ($accountid, $roles))
		);
	}
	
	/**
	 *	Check
	 *	Perform allow function, throw exception if not allowed.
	 *
	 *	@param	int		$accountid
	 *	@param	array	$roles
	 *	@return void
	 *	@throws \InvalidUserException
	 */
	public static function check ($accountid = null, $roles = array())
	{	
		if (!self::allowed($accountid, $roles))
			
			throw new \InvalidUserException ('not authorized');
	}
	
	/**
	 *	User
	 *	Show current user.
	 *
	 *	@param	string	$token
	 *	@return User
	 */
	public static function user ($token)
	{			
		if (!$token)
			
			throw new \InvalidParameterException ('an access token is required');
		
		
		return Oauth2AccessToken::validated ($token)->first()->user;
		
		//return Oauth2Verifier::getUser ();
	}
	
	/**
	 *	Valid access
	 *	Make sure the user has a valid access token.
	 *
	 *	@return boolean
	 */
	public static function validAccess ($token)
	{	
		# Is the token provided?
		if(!$token) return false;
		
		# Does token exist within expiration scope?
		return (bool) Oauth2AccessToken::validated ($token)->count ();
	}
	
	/**
	 *	Account Relation
	 *	Make sure the user is related to the account.
	 *
	 *	the where $apk function should be a find function. Look for new Eloquent versions solving the current bug.
	 *
	 *	@return boolean
	 *	@throws \InvalidParameterException
	 */
	public static function accountRelation ($token, $id)
	{	
		if (!is_int ($id))
			
			throw new \InvalidParameterException ('an integer ID is required');
		
		// User contains account
		return self::user ($token)-> accounts->contains ($id);
	}
	
	/**
	 *	Has Roles
	 *	Make sure the user has all the required roles for the related account.
	 *
	 *  @param  int		$id			Account id to retreive rolesset from
	 *  @param  string	$role		Required role
	 *	@return boolean
	 *	@throws \InvalidParameterException
	 *	
	 *	User vs Account rolesset as array:
	 *	return count ($roles) === count (array_intersect (self::user()->getRoles ($id), $roles));
	 *
	 */
	public static function hasRoles ($id, $role)
	{
		if(!isset ($role) || !is_string ($role))
		
			throw new \InvalidParameterException ('Invalid roletokens container type');
	
		
		// User vs Account rolesset
		return in_array ($role, self::user()->getRoles ($id));
	}
}
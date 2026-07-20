<?php
 use App\Models\Category;
 use App\Models\Unit;
 use App\Models\CurrencySetting;
 use App\Models\Role;
 use App\Models\CommissionSetting;
 use App\Models\Subcategory;
 use App\Models\User;

 function user()
 {
 	$user = auth()->user();
 	return $user;
 }

 function categories()
 {
 	$categories = Category::where('status','Active')->latest()->get();
 	return $categories;
 }

 function units()
 {
 	$units = Unit::latest()->get();
 	return $units;
 }

 function successMessage($section,$action)
 {  
 	$article = "the";
 	if($action == "add")
 	{
 		$action="added";
 		$article = "a";
 	}else if($action == "update"){
 		$action="updated";
 	}else{
 	    $action = "deleted";
 	}	
 	
 	$message = "Successfully {$article} {$section} has been {$action}";
 	return $message;
 }

 function errorMessage($section,$action)
 {   
 	//$action.="ed";
 	$message = "Failed to {$action}";
 	return $message;
 }

function categorySlug($category_name)
{
    $slug = \Str::slug($category_name);

    $count = \App\Models\Category::where('slug', 'LIKE', $slug . '%')->count();

    return $count ? $slug . '-' . ($count + 1) : $slug;
}

function currency(){
	$data = CurrencySetting::find(1);
	return $data;
}

function roles()
{   
	$ids = [1,2,3];
	$roles = Role::whereNotIn('id',$ids)->latest()->get();
	return $roles;
	
}

function fee()
{
	$data = CommissionSetting::find(1);
	return $data;
}

function subcategories()
{
	$subcategories = Subcategory::where('status','Active')->latest()->get();
	return $subcategories;
}

function vendors()
{
	$vendors = User::where('role_id',2)->latest()->get();
	return $vendors;
}
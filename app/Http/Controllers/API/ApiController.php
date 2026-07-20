<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderDetail;
use Auth;
use DB;
use Validator;
use App\Services\Product\ProductService;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\Variant\VariantService;

class ApiController extends Controller
{   

    public function __construct(
        protected ProductService $productService,
        protected VariantService $variantService
    ) {
        //$this->middleware('auth_check');
    }

    public function userSignup(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50',
                'phone' => 'required|string',
                'email' => 'nullable|email',
                'password' => 'required|string',
                'confirm_password' => 'required|string|same:password'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $user = new User();

            if($request->has('email')){
                $countEmail = User::where('email',$request->email)->count();
                if($countEmail > 0){
                    return response()->json(['status'=>false, 'message'=>"Already the email has been taken", "data"=>new \stdClass()],403);
                }
            }

            if($request->has('phone')){
                $countPhone = User::where('phone',$request->phone)->count();
                if($countPhone > 0){
                    return response()->json(['status'=>false, 'message'=>"Already the phone has been taken", "data"=>new \stdClass()],403);
                }
            }

            $user = new User();
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(['status'=>true, 'message'=>"Successfully Signup", "data"=>$user],201);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function userSignin(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $login = $request->input('login');
            $password = $request->input('password');

            $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

            $user = User::where('email',$login)->orWhere('phone',$login)->first();
            
            if($user->status == 'Inactive'){
                return response()->json(['status'=>false, 'message'=>'Sorry you are not active user', 'token'=>"", 'user'=>new \stdClass()],403);
            }

            if (Auth::attempt([$fieldType => $login, 'password' => $password])) {
                $token = $user->createToken('MyApp')->plainTextToken;
                return response()->json(['status'=>true,'message'=>'Successfully Logged IN', 'token'=>$token, 'user'=>$user]);
            }

            return response()->json(['status'=>false,'message'=>"Invalid Email/Phone or Password", 'token'=>"", 'user'=>new \stdClass()],401);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function userSignOut(Request $request)
    {
        try
        {
            auth()->user()->tokens()->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully Logged Out']);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }


    public function vendorSignup(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Upload Files
            |--------------------------------------------------------------------------
            */

            $validator = Validator::make($request->all(), [

                // USER
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|unique:users,email',
                'phone'                 => 'required|string|max:20|unique:users,phone',
                'password'              => 'required|string|min:6|confirmed',

                // VENDOR
                'shop_name'             => 'required|string|max:255',
                'nid_number'            => 'required|string|max:100|unique:vendors,nid_number',

                'nid_front'             => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'nid_back'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'selfie_photo'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

                'trade_license_no'      => 'nullable|string|max:255',

                'tin_no'                => 'nullable|string|max:255',
                'tin_file'              => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

                'bin_no'                => 'nullable|string|max:255',
                'bin_file'              => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

                'bank_name'             => 'nullable|string|max:255',
                'branch_name'           => 'nullable|string|max:255',
                'account_name'          => 'nullable|string|max:255',
                'account_number'        => 'nullable|string|max:255',

                'cancelled_cheque'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

                'pickup_address'        => 'nullable|string',
                'return_address'        => 'nullable|string',

            ], [

                'name.required'                 => 'Full name is required.',

                'email.required'                => 'Email is required.',
                'email.email'                   => 'Enter a valid email address.',
                'email.unique'                  => 'Email already exists.',

                'phone.required'                => 'Phone number is required.',
                'phone.unique'                  => 'Phone number already exists.',

                'password.required'             => 'Password is required.',
                'password.min'                  => 'Password must be at least 6 characters.',
                'password.confirmed'            => 'Password confirmation does not match.',

                'shop_name.required'            => 'Shop name is required.',

                'nid_number.required'           => 'NID number is required.',
                'nid_number.unique'             => 'This NID number is already registered.',

                'nid_front.image'               => 'NID Front must be an image.',
                'nid_back.image'                => 'NID Back must be an image.',
                'selfie_photo.image'            => 'Selfie must be an image.',

                'tin_file.mimes'                => 'TIN file must be JPG, PNG or PDF.',
                'bin_file.mimes'                => 'BIN file must be JPG, PNG or PDF.',
                'cancelled_cheque.mimes'        => 'Cancelled cheque must be JPG, PNG or PDF.',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }


            $nidFront = null;
            $nidBack = null;
            $selfie = null;
            $tinFile = null;
            $binFile = null;
            $cancelCheque = null;

            if ($request->hasFile('nid_front')) {

                $file = $request->file('nid_front');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $nidFront = 'uploads/vendors/'.$name;
            }

            if ($request->hasFile('nid_back')) {

                $file = $request->file('nid_back');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $nidBack = 'uploads/vendors/'.$name;
            }

            if ($request->hasFile('selfie_photo')) {

                $file = $request->file('selfie_photo');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $selfie = 'uploads/vendors/'.$name;
            }

            if ($request->hasFile('tin_file')) {

                $file = $request->file('tin_file');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $tinFile = 'uploads/vendors/'.$name;
            }

            if ($request->hasFile('bin_file')) {

                $file = $request->file('bin_file');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $binFile = 'uploads/vendors/'.$name;
            }

            if ($request->hasFile('cancelled_cheque')) {

                $file = $request->file('cancelled_cheque');

                $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

                $file->move(public_path('uploads/vendors'), $name);

                $cancelCheque = 'uploads/vendors/'.$name;
            }

            /*
            |--------------------------------------------------------------------------
            | Create User
            |--------------------------------------------------------------------------
            */

            $user = User::create([

                'name' => $request->name,

                'email' => $request->email,

                'phone' => $request->phone,

                'role_id' => 2,

                'password' => Hash::make($request->password),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Vendor
            |--------------------------------------------------------------------------
            */

            Vendor::create([

                'user_id' => $user->id,

                'shop_name' => $request->shop_name,

                'nid_number' => $request->nid_number,

                'nid_front' => $nidFront,

                'nid_back' => $nidBack,

                'selfie_photo' => $selfie,

                'trade_license_no' => $request->trade_license_no,

                'tin_no' => $request->tin_no,

                'tin_file' => $tinFile,

                'bin_no' => $request->bin_no,

                'bin_file' => $binFile,

                'bank_name' => $request->bank_name,

                'branch_name' => $request->branch_name,

                'account_name' => $request->account_name,

                'account_number' => $request->account_number,

                'cancelled_cheque' => $cancelCheque,

                'pickup_address' => $request->pickup_address,

                'return_address' => $request->return_address,

            ]);

            DB::commit();

            //return redirect('/vendor-login');

            // return redirect()
            //     ->route('vendor.signup.form')
            //     ->with('success', 'Vendor account created successfully.');

            return response()->json(['status'=>true, 'message'=>'Successfully Vendor Signup', 'data'=>$vendor]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);

        }
    }


    public function vendorSignin(Request $request)
    {
        try {

            
            $validator = Validator::make($request->all(), [
                'login' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $vendor = User::where('email',$request->login)->orWhere('phone',$request->login)->where('role_id',2)->first();

            if($vendor)
            {
                Auth::login($vendor);
                $token = $vendor->createToken('MyApp')->plainTextToken;
                return response()->json(['status'=>true, 'message'=>'Successfully Logged IN', 'token'=>$token, 'data'=>$vendor]);
            }

            return response()->json(['status'=>false, 'message'=>"Email/Phone Or Password Invalid", 'token'=>"", 'data'=>new \stdClass()],401);    

        } catch (\Exception $e) {

            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
            // return back()
            //     ->withInput()
            //     ->with('error', $e->getMessage());
        }
    }

    public function vendorSignout(Request $request)
    {
        try
        {
            auth()->user()->tokens()->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully Logged Out']);
        }catch (\Exception $e) {

            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
            // return back()
            //     ->withInput()
            //     ->with('error', $e->getMessage());
        }
    }



    public function saveOrder(Request $request)
    {   
    	DB::beginTransaction();
    	try
    	{   

    		$validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|unique:products,id',
                'user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

    		$order = new Order();
    		$order->user_id = user()->id;
    	}catch(\Exception $e){
    		DB::rollback();
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function saveProduct(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'product_name'      => 'required|string|max:255|unique:products,product_name',
                'category_id'       => 'required|exists:categories,id',
                'subcategory_id'    => 'nullable|exists:subcategories,id',
                'unit_id'           => 'required|exists:units,id',

                'product_price'     => 'required|numeric|min:1',
                'product_discount'  => 'nullable|numeric|min:1',
                'stock_qty'         => 'required|numeric|min:1',

                'description'       => 'required|string',

                'featured_image'    => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

                'status'            => 'nullable|in:Active,Inactive',
                'admin_verify'      => 'nullable|in:Yes,No',
            ], [
                'product_name.required' => 'Product name is required',
                'category_id.required'  => 'Category is required',
                'unit_id.required'      => 'Unit is required',
                'featured_image.required'=> 'Product image is required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            } 


            $this->productService->store($request->validated());

            return response()->json(['status'=>true, 'message'=>'Successfully a product has been added']);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function categoryLists(Request $request)
    {
        try
        {   
            
            $query = Category::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('category_name', 'LIKE', "%{$search}%");
            }
            $data = $query->where('status','Active')->latest()->get();
            return response()->json(['status'=>count($data) > 0, 'data'=>$data]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function subcategoryLists(Request $request)
    {
        try
        {
            $query = Subcategory::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('category_name', 'LIKE', "%{$search}%");
            }
            $data = $query->where('status','Active')->latest()->get();
            return response()->json(['status'=>count($data) > 0, 'data'=>$data]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productLists(Request $request)
    {
        try
        {   

            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $per_page = $request->has('per_page')?$request->per_page:10;
            $query = Product::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('product_name', 'LIKE', "%{$search}%");
            }

            if($request->has('category_id'))
            {
                $query->where('category_id',$request->category_id);
            }

            if($request->has('subcategory_id'))
            {
                $query->where('subcategory_id',$request->subcategory_id);
            }

            $data = $query->with('category','subcategory','productVariants')->where('user_id',user()->id)->latest()->paginate($per_page);
            return response()->json($data);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function vendorproductDetails($id)
    {
        try
        {
            $product = Product::with('category','subcategory','productVariants')->findorfail($id);
            return response()->json(['status'=>true, 'data'=>$product]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productUpdate(Request $request,$id)
    {
        try
        {
            $productId = $id;

            $validator = Validator::make($request->all(), [

                'product_name'      => 'required|string|max:255|unique:products,product_name,' . $productId,
                'category_id'       => 'required|exists:categories,id',
                'subcategory_id'    => 'nullable|exists:subcategories,id',
                'unit_id'           => 'required|exists:units,id',

                'product_price'     => 'required|numeric|min:0',
                'product_discount'  => 'nullable|numeric|min:0',
                'stock_qty'         => 'required|numeric|min:0',

                'description'       => 'required|string',

                'featured_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

                'status'            => 'nullable|in:Active,Inactive',
                'admin_verify'      => 'nullable|in:Yes,No',

            ], [

                'product_name.required' => 'Product name is required.',
                'product_name.unique'   => 'Product name already exists.',

                'category_id.required'  => 'Category is required.',
                'category_id.exists'    => 'Selected category is invalid.',

                'subcategory_id.exists' => 'Selected subcategory is invalid.',

                'unit_id.required'      => 'Unit is required.',
                'unit_id.exists'        => 'Selected unit is invalid.',

                'product_price.required' => 'Product price is required.',
                'product_price.numeric'  => 'Product price must be a number.',
                'product_price.min'      => 'Product price must be at least 0.',

                'product_discount.numeric' => 'Product discount must be a number.',
                'product_discount.min'     => 'Product discount must be at least 0.',

                'stock_qty.required' => 'Stock quantity is required.',
                'stock_qty.numeric'  => 'Stock quantity must be a number.',
                'stock_qty.min'      => 'Stock quantity must be at least 0.',

                'description.required' => 'Description is required.',

                'featured_image.image' => 'Featured image must be an image.',
                'featured_image.mimes' => 'Featured image must be a JPG, JPEG, PNG or WEBP file.',
                'featured_image.max'   => 'Featured image may not be greater than 2 MB.',

                'status.in'       => 'Status must be Active or Inactive.',
                'admin_verify.in' => 'Admin verify must be Yes or No.',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $product = Product::with('category','subcategory','productVariants')->findorfail($productId);

            $this->productService->update($request->validated(), $product);

            $product->refresh();

            return response()->json(['status'=>true, 'message'=>'Successfully updated', 'data'=>$product]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productDelete($id)
    {
        try
        {   
            $product = Product::findorfail($id);
            $this->productService->delete($product);

            return response()->json([
                'status' => true,                         
                'message' => 'Product deleted successfully'
            ]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productStatusUpdate(Request $request)
    {
        try
        {  

            $this->productService->updateStatus(
                $request->product_id,
                $request->status
            );

            return response()->json([
                'status'  => true,
                'message' => 'Status updated successfully'
            ]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function variantLists(Request $request)
    {
        try
        {
            $data = Variant::get();
            return response()->json(['status'=>count($data) > 0, 'data'=>$data]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productVariantLists($id)
    {
        try
        {
            $product = Product::findorfail($id);
            $variants = Variant::with(['productVariants' => function ($query) use ($id) {
                $query->where('product_id', $id);
            }])->get();
            return response()->json(['status'=>count($variants) > 0, 'data'=>array('product'=>$product,'variants'=>$variants)]); 
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function homeCategories(Request $request)
    {
        try
        {
            $categories = Category::with([
                        'subcategories' => function ($query) {
                            $query->where('status', 'Active');
                        }
                    ])
                    ->where('status', 'Active')
                    ->whereHas('subcategories', function ($query) {
                        $query->where('status', 'Active');
                    })
                    ->take(12)
                    ->get();

            return response()->json(['status'=>count($data) > 0, 'data'=>$categories]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function latestProducts(Request $request)
    {
        try
        {
            $per_page = $request->has('per_page')?$request->per_page:10;
            $query = Product::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('product_name', 'LIKE', "%{$search}%");
            }

            // if($request->has('category_id'))
            // {
            //     $query->where('category_id',$request->category_id);
            // }

            // if($request->has('subcategory_id'))
            // {
            //     $query->where('subcategory_id',$request->subcategory_id);
            // }

            $data = $query->with('category','subcategory','productVariants')->latest()->paginate($per_page);
            return response()->json($data);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function bestSeller(Request $request)
    {
        try
        {
            $per_page = $request->has('per_page')?$request->per_page:10;
            $query = Product::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('product_name', 'LIKE', "%{$search}%");
            }

            // if($request->has('category_id'))
            // {
            //     $query->where('category_id',$request->category_id);
            // }

            // if($request->has('subcategory_id'))
            // {
            //     $query->where('subcategory_id',$request->subcategory_id);
            // }

            $data = $query->with('category','subcategory','productVariants')->paginate($per_page);
            return response()->json($data);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function adminVerifyProducts(Request $request)
    {
        try
        {
            $per_page = $request->has('per_page')?$request->per_page:10;
            $query = Product::query();
            if($request->has('search'))
            {
                $search = $request->search;
                $query->where('product_name', 'LIKE', "%{$search}%");
            }

            // if($request->has('category_id'))
            // {
            //     $query->where('category_id',$request->category_id);
            // }

            // if($request->has('subcategory_id'))
            // {
            //     $query->where('subcategory_id',$request->subcategory_id);
            // }

            $data = $query->with('category','subcategory','productVariants')->where('admin_verify','Yes')->latest()->paginate($per_page);
            return response()->json($data);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function categoryDetails(Request $request,$id)
    {
        try
        {   
            $per_page = $request->has('per_page')?$request->per_page:10;
            $category = Category::findorfail($id);
            $products = Product::with('category','subcategory','productVariants')->where('category_id',$id)->where('status','Active')->latest()->paginate($per_page);
            return response()->json(['stauts'=>true, 'data'=>array('category'=>$category,'products'=>$products)]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function subcategoryDetails(Request $request,$id)
    {
        try
        {   
            $per_page = $request->has('per_page')?$request->per_page:10;
            $subcategory = Subcategory::findorfail($id);
            $products = Product::with('category','subcategory','productVariants')->where('subcategory_id',$id)->where('status','Active')->latest()->paginate($per_page);
            return response()->json(['stauts'=>true, 'data'=>array('subcategory'=>$subcategory,'products'=>$products)]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function productDetails($id)
    {
        try
        {
            //$categories = Category::where('status','Active')->latest()->take(10)->get();
            $product = Product::with('category','unit','productVariants.variant','user')->where('slug',$slug)->first();
            $relatedProducts = Product::where('category_id',$product->category_id)->where('id','!=',$product->id)->take(12)->get();

            //$cartCount = Cart::where('cart_session_id',Session::get('cart_session_id'))->count();

            $productVariants = $product->productVariants()->get();

            $productImageVariants = $product->productVariants()
                            ->whereNotNull('image')
                            ->get();

            $variants = Variant::whereHas('productVariants')->with(['productVariants' => function ($q) use ($product) {
                $q->where('product_id', $product->id);
            }])->get();

            $data = array('product'=>$product, 'variants'=>$productVariants, 'variant_images'=>$productImageVariants, 'relatedProducts'=>$relatedProducts);

            return response()->json(['status'=>true, 'data'=>$data]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function shop(Request $request)
    {
        try
        {   
            $per_page = $request->has('per_page')?$request->per_page:10;
            $query = Product::query();
            if($request->has('value')){
                if($request->value == 'hit_count'){
                    $query->orderBy('hit_count','DESC');
                }else if($request->value == 'latest'){
                    $query->latest();
                }else if($request->value == 'low_to_high'){
                    $query->orderBy('product_price','ASC');
                }else if($request->value == 'high_to_low'){
                    $query->orderBy('product_price','DESC');
                }
            }

            $products = $query->with('user','category')->where('status','Active')->latest()->paginate($per_page);
            
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    // public function products(Request $request)
    // {
    //     try
    //     {
    //         $validator = Validator::make($request->all(), [
    //             'per_page' => 'nullable|integer'
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false, 
    //                 'message' => 'Please fill all requirement fields', 
    //                 'data' => $validator->errors()
    //             ], 422);  
    //         }

    //         $per_page = $request->has('per_page')?$request->per_page:10;
    //         $query = Product::query();
    //         if($request->has('search'))
    //         {
    //             $search = $request->search;
    //             $query->where('product_name', 'LIKE', "%{$search}%");
    //         }

    //         if($request->has('category_id'))
    //         {
    //             $query->where('category_id',$request->category_id);
    //         }

    //         if($request->has('subcategory_id'))
    //         {
    //             $query->where('subcategory_id',$request->subcategory_id);
    //         }


    //         if($request->has('value')){
    //             if($request->value == 'hit_count'){
    //                 $query->orderBy('hit_count','DESC');
    //             }else if($request->value == 'latest'){
    //                 $query->latest();
    //             }else if($request->value == 'low_to_high'){
    //                 $query->orderBy('product_price','ASC');
    //             }else if($request->value == 'high_to_low'){
    //                 $query->orderBy('product_price','DESC');
    //             }
    //         } 

    //         // $categories = Category::whereHas('products')->where('status','Active')->latest()->take(12)->get();

    //         //$products = $query->with('user','category')->where('status','Active')->latest()->paginate(9);



    //          //return view('shop',compact('categories','products'));

    //         $data = $query->with('category','subcategory','productVariants')->where('user_id',user()->id)->latest()->paginate($per_page);

    //         return response()->json($data);

    //     }catch(\Exception $e){
    //         return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
    //     }
    // }
}

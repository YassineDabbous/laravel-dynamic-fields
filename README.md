
# Laravel Dynamic Fields 

Reduce the overall size of your SQL query by selecting only what you need.

### ✨ Features 

As the API user (FrontEnd developer) don't need to know which fields are columns, appends or model relationships, this package helps him to handle 
- selecting columns
- appending attributes
- loading relationships 
- applying aggregates 

automatically via a single URL parameter: **_fields**

### 🔻 Installation

    composer require yassinedabbous/laravel-dynamic-fields
    
### 🧑‍💻  Basic Setup

Add **HasDynamicFields** trait to your Model class and define your columns:

```php
use YassineDabbous\DynamicFields\HasDynamicFields;

class User extends Model
{
    use HasDynamicFields;

    /** Allowed table columns */
    public function dynamicColumns(): array
    {
        return ['id', 'name', 'avatar', 'birthday', 'created_at'];
    }

	/** Appends and their dependencies */
    public function dynamicAppendsDepsColumns() { 
        return [
            'age' => 'birthday',
		 ];
	}
}
```

Dynamic selection isn't automatically applied, you need to call *dynamicSelect()*  and *dynamicAppend()* :

```php
class UserController
{
    public function index()
    {
        $collection = User::dynamicSelect()->paginate(); 
        $collection->dynamicAppend();
        return $collection;
    }
}
```
### 🧑‍💻 Usage
• API call: 

	GET /users?fields=id,name,age
	
• DB query:

	SELECT "id", "name" FROM "users"


• Response: ("age" appended automatically)

		{
			"id": 1,
			"name": "Someone",
			"age": 99,
		}

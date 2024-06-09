
# Laravel Dynamic Fields 

Make your API simple & Reduce the overall size of your SQL query by selecting only what you need.

### ✨ Features 

As the API user (FrontEnd developer) don't need to know which fields are columns, appends or model relationships, this package helps him to handle 
- selecting columns
- appending attributes
- loading relationships 
- applying aggregates 

automatically via a single URL parameter: **_fields**

### 🔻 Installation

    composer require yassinedabbous/laravel-dynamic-fields
    
### 🧑‍💻  Setup

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

    public function dynamicRelations(){
        return ['posts', 'recent_comments'];
    }

	/** Appends and their dependencies */
    public function dynamicAppendsDepsColumns() { 
        return [
            'age' => 'birthday',
        ];
	}

    /** aggregates closures */
    public function dynamicAggregates(){
        return [
            'posts_count' => fn($q) => $q->withCount('posts'),
            'points' => fn($q) => $q->withSum('matches', 'score'),
        ];
    }



    // Model attributes & relations ...

    public function age(): Attribute
    {
        return Attribute::get(fn() => Carbon::parse($this->birthdate)->age);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
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

	GET /users?fields=id,name,age,points,recent_comments
	
• DB query:

    // select only "id", "name"
	SELECT "id", "name" FROM "users"

    // aggregates
	SELECT SUM("score") FROM "matches" where ...

    // relation loaded
	SELECT * FROM "comments" where "date" > ...

• Response: ("age", "points", "recent comments" are automatically appended)

    {
        "id": 1,
        "name": "Someone",
        "age": 99,
        "points": 1048,
        "recent_comments": {...}
    }

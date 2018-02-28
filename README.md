MWP Application Framework for WordPress
==================================

The [MWP Application Framework][7] allows WordPress plugins to be developed using modern development patterns such as MVC and MVVM. Any plugin created with it can be distributed standalone as the framework classes are bundled in when it is built.

It provides simple solutions for [modeling data][2], [building forms][5], [running tasks][6], [creating interfaces][4], [templating][3], [testing][9], [packaging][10], and writing [generally extensible][11] code.

Get started with the [Installation & Setup][8] guide.

View the [changelog][12].

**Here are a few highlights:**

* * *

### @ Annotations

Use annotations to interface easily with WordPress core.

```php
/**
 * Register a script located in plugin-dir/assets/js folder
 *
 * @MWP\WordPress\Script( handle="my_custom_js" )
 */
public $jsModule = 'assets/js/my_js_module.js';   


/**
 * Use your custom script and pass it the current user id
 *
 * @MWP\WordPress\Action( for="wp_enqueue_scripts" )
 */
public function enqueueScripts() {
	MyPlugin::instance()->useScript( $this->jsModule );
}  

/**
 * Wrap all post content with some html
 * 
 * @MWP\WordPress\Filter( for="the_content" )
 */
public function addContent( $the_content ) {
	return "<div>" . $the_content . "</div>";
}    
```   

View the [Annotations Reference][1] documentation.

* * *

### @ Models

Use active records to model your data and attach behaviors to it. Quickly create, read, update, and delete database records.

```php
use VendorName\VendorPackage\RecordClass;

$record = new RecordClass;
$record->message = "A developer is a device for turning coffee into code.";
$record->save();
```    

View the [Active Record][2] documentation.

* * *

### @ Views

Use views (templates) to separate your presentation (html) from your business logic (code). This allows easy refactoring of output, and allows themes to easily override them.

```html
<!-- File: plugin-dir/templates/views/component/quote.php -->
<blockquote>
  <?php echo $quote ?>
</blockquote>
```

```php
/* Get some html to output */
use VendorName\VendorPackage\RecordClass;

$record = current(RecordClass::loadWhere('TRUE'));
$html = MyPlugin::instance()->getTemplateContent( 'views/component/quote', [
  'quote' => $record ? $record->message : 'Silence is bliss.',
]);

echo $html;
```    

View the [Templating][3] documentation.

* * *

### @ Controllers

Deploy a management interface to the WP Admin to view, create, update, and delete custom database records.

```php
use VendorName\PackageName\RecordClass;

add_action( 'init', function() {
	$controller = RecordClass::createController('admin');
	$controller->registerAdminPage([
		'title' => __( 'Record Management' ),
		'type' => 'menu', 
		'slug' => 'vendor-slug', 
		'menu' => __( 'Acme' ), 
		'icon' => $plugin->fileUrl( 'assets/img/icon.png' ), 
		'position' => 76,
	]);
});
```    

View the [Active Record Controller][4] documentation.

* * *

### @ Forms

Build forms and check their submission values in a few lines of code.

```php
/* Create a form and add some fields */
$form = MyPlugin::instance()->createForm('user_questions');
$form->addField('name', 'text', ['label' => 'Enter Your Name', 'required' => true]);
$form->addField('age', 'number', ['label' => 'Age', 'attr' => ['min' => 5]);

/* Process form if it was just submitted */
if ( $form->isValidSubmission() ) {
	$values = $form->getValues();
	update_user_meta( get_current_user_id(), 'name', $values['name'] );
	update_user_meta( get_current_user_id(), 'age', $values['age'] );
	$form->processComplete( function() {
		wp_redirect( home_url() );
		exit;
	});
}

/* Output the form, indicating errors if form was incorrectly submitted */
echo $form->render();
```    

View the [Forms][5] documentation.

* * *

### @ Tasks

Send data to a task queue to be processed by a job runner asynchronously.

```php
/* Queue a task */
$user_ids = get_users([ 'fields' => 'ID' ]);
Task::queueTask([ 'action' => 'callback_hook_name' ], [ 'user_ids' => $user_ids ]);

/**
 * @MWP\WordPress\Action( for="callback_hook_name" )
 */
public function processUsers( $task ) {
	$data = $task->getData('user_ids');
	if ( empty( $data ) ) {
	   return $task->complete();
	}    
	$user_id = array_shift($data);

	// process user ...

	$task->setData($data);
}
```    

View the [Tasks][6] documentation.



 [1]: https://www.codefarma.com/docs/mwp-framework/annotations
 [2]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/models
 [3]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/templating
 [4]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/record-controller
 [5]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/forms
 [6]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/tasks
 [7]: https://www.codefarma.com/docs/mwp-framework
 [8]: https://www.codefarma.com/docs/mwp-framework/setup
 [9]: https://www.codefarma.com/docs/mwp-framework/guide/testing
 [10]: https://www.codefarma.com/docs/mwp-framework/guide/distribute
 [11]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/extensibility
 [12]: https://github.com/codefarma/mwp-framework/blob/master-2.x/changelog.md
 
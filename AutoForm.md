AutoForm is a Zend\_Form that directly ties to a model object and maps its properties into form fields. An AutoForm can be directly populated using an object instance (populate() has been overloaded to accept an object as its parameter), and the exact field behavior can be customized through the use of annotations on the model object itself.


---


## Available annotations ##
Below are the available annotations for the generated form fields:
  * **@FormFieldLabel `<label>`**
  * **@FormFieldType `<hidden|text|password|checkbox|textarea|recursive|recursiveid|parentselect>`** - Defaults to text.
  * **@FormFieldDisabled `<true|false>`** - Defaults to false.
  * **@FormFieldRequired `<true|false>`** - Defaults to false.
  * **@FormFieldMaxOccurs `<num>`** - Defaults to 20.

For an attribute to be mapped to the AutoForm, at minimum the annotation @FormFieldLabel must be exist.


---


## Recursive Form Fields ##
The last three options in the @FormFieldType annotation are special. They do not create fields based on the object's attributes themselves, but rather based on the types of the objects they reference. The referenced fields are determined either based on Doctrine annotations, or by phpDoc's @var `<type>` annotation.

The following recursive field types are available:
### recursive ###
Creates a subform which is an AutoForm for the referenced object class. The referenced class needs to be annotated in the same manner as any class intended to be used by AutoForm, and can contain simple fields or other recursive fields for multi-level nesting.

### recursiveid ###
This is similar to the recursive type, but only adds the field designated by Doctrine as ids (ie. contain the @Id annotation) to the generated subform. This is useful for adding reference fields (such as a hidden field containing the referenced object's id) to the AutoForm, while also allowing the referenced class to be used elsewhere as a fully-fledged standalone AutoForm.

### parentselect ###
Generates a select field for a Many to One relation. This type utilizes Doctrine's EntityManager (which is assumed to be stored in Zend\_Registry as entityManager) to find all the instances of the class it references and to subsequently populate the select field's options with them.


---


## Example usage ##
### Simple Login Form ###
Assume we have the following class definition representing a User in our application:
```
<?php
class Application_Model_User {
	public $_username;
	public $_password;
}
```

To create a login form, we would annotate our class as follows and then create an AutoForm in our controller with the model class as its parameter:
```
<?php
class Application_Model_User {
	/**
	* @FormFieldLabel Username
	* @FormFieldRequired true
	*/
	public $_username;
	/**
	* @FormFieldLabel Username
	* @FormFieldType password
	* @FormFieldRequired true
	*/
	public $_password;
}
```

```
<?php
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
	$this->view->form = new Dnna_Form_AutoForm('Application_Model_User');
    }
}
```

And that's it! We have created a simple form with a text and a password field, which will automatically update if we decide to refactor our model.
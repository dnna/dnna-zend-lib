GenerateXsd is an action helper that receives a Zend\_Form as it's input and generates an XSD schema based on the form's elements and validators. GenerateXsd supports subforms and will recursively create the required ComplexType elements.

GenerateXsd will also generate comments for each element based on the element's label in the form and also for Select type choices.
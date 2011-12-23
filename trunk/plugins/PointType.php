<?php
namespace Dnna\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
 
/**
 * Mapping type for spatial POINT objects
 * Modified from http://codeutopia.net/blog/2011/02/19/using-spatial-data-in-doctrine-2/
 */
class PointType extends Type {
    const POINT = 'point';
 
    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName() {
        return self::POINT;
    }
 
    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        return 'POINT';
    }
 
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        //Null fields come in as empty strings
        if($value == '') {
            return null;
        }
 
        $data = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $value);
        return new Dnna_Model_Point($data['lat'], $data['lon']);
    }
 
    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        if (!$value) return;
 
        return pack('xxxxcLdd', '0', 1, $value->getLatitude(), $value->getLongitude());
    }
}

Type::addType('point', 'Dnna\Doctrine\Types\PointType');
?>
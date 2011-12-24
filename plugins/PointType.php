<?php
namespace Dnna\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

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
        return new \Dnna_Model_Point($data['lat'], $data['lon']);
    }
 
    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        if (!$value) return;
 
        return pack('xxxxcLdd', '0', 1, $value->get_latitude(), $value->get_longitude());
    }
}

/**
 * POINT_STR function for querying using Point objects as parameters
 *
 * Usage: POINT_STR(:param) where param should be mapped to $point where $point is Wantlet\ORM\Point
 *        without any special typing provided (eg. so that it gets converted to string)
 * Modified from http://codeutopia.net/blog/2011/02/19/using-spatial-data-in-doctrine-2/
 */
class PointStr extends FunctionNode {
    private $arg;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
        return 'GeomFromText(' . $this->arg->dispatch($sqlWalker) . ')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->arg = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

}

/**
 * DQL function for calculating distances between two points
 *
 * Example: DISTANCE(foo.point, POINT_STR(:param))
 * Modified from http://codeutopia.net/blog/2011/02/19/using-spatial-data-in-doctrine-2/
 */
class Distance extends FunctionNode {
    private $firstArg;
    private $secondArg;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
        //Need to do this hacky linestring length thing because
        //despite what MySQL manual claims, DISTANCE isn't actually implemented...
        return 'GLength(LineString(' .
               $this->firstArg->dispatch($sqlWalker) .
               ', ' .
               $this->secondArg->dispatch($sqlWalker) .
           '))';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstArg = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondArg = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}

Type::addType('point', 'Dnna\Doctrine\Types\PointType');
?>
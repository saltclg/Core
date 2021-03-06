<?php
namespace exface\Core\Widgets;

/**
 * Planograms are special diagrams used for visual placement of objects in 2D-space: e.g.
 * products on a shelf or furniture in a room.
 *
 * @author Andrej Kabachnik
 *        
 */
class Planogram extends Diagram
{

    private $add_row_link_button_id = null;

    public function getAddRowLinkButtonId()
    {
        return $this->add_row_link_button_id;
    }

    public function setAddRowLinkButtonId($value)
    {
        $this->add_row_link_button_id = $value;
        return $this;
    }
}

?>

<?php
namespace exface\Core\Widgets;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Exceptions\MetaModelBehaviorException;

class StateMenuButton extends MenuButton {
	
	private $smb_buttons = array();
	
	/**
	 * (non-PHPdoc)
	 * @see \exface\Core\Interfaces\Widgets\iHaveButtons::get_buttons()
	 */
	public function get_buttons() {
		//Falls am Objekt ein StateMachineBehavior haengt wird versucht den momentanen Status aus
		//dem Objekt auszulesen und die entsprechenden Buttons aus dem Behavior hinzuzufuegen
		if (!$this->smb_buttons) {
			if ($smb = $this->get_meta_object()->get_behaviors()->get_by_alias('exface.Core.Behaviors.StateMachineBehavior')) {
				$template = $this->get_ui()->get_template_from_request();
				if ((($data_sheet = $this->get_prefill_data()) || ($data_sheet = $template->get_data_sheet_from_request($template->get_request_object_id())))
						&& ($state_column = $data_sheet->get_column_values($smb->get_state_attribute_alias()))) {
					$current_state = $state_column[0];
				}
				
				foreach ($smb->get_state_buttons($current_state) as $smb_button) {
					$button = $this->get_page()->create_widget('DialogButton', $this, UxonObject::from_anything($smb_button));
					$button->set_parent($this);
					$button->set_input_widget($this->get_input_widget());
					$this->smb_buttons[] = $button;
				}
			} else {
				throw new MetaModelBehaviorException('StateMenuButton: The object '.$this->get_meta_object()->get_alias_with_namespace().' has no StateMachineBehavior attached.');
			}
		}
		
		return array_merge(parent::get_buttons(), $this->smb_buttons);
	}
}
?>
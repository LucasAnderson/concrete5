<?
defined('C5_EXECUTE') or die(_("Access Denied."));
class CollectionAttributeKey extends AttributeKey {

	/** 
	 * Returns an attribute value list of attributes and values (duh) which a collection version can store 
	 * against its object.
	 * @return AttributeValueList
	 */
	public function getAttributes($cID, $cvID) {
		$db = Loader::db();
		$values = $db->GetAll("select akID, avID from CollectionAttributeValues where cID = ? and cvID = ?", array($cID, $cvID));
		$avl = new AttributeValueList();
		foreach($values as $val) {
			$ak = CollectionAttributeKey::getByID($val['akID']);
			$value = $ak->getAttributeValue($val['avID']);
			$avl->addAttributeValue($ak, $value);
		}		
		return $avl;
	}
	
	public static function getByID($akID) {
		$ak = new CollectionAttributeKey();
		$ak->load($akID);
		return $ak;
	}

	public static function getByHandle($akHandle) {
		$db = Loader::db();
		$akID = $db->GetOne('select akID from AttributeKeys where akHandle = ?', array($akHandle));
		$ak = new CollectionAttributeKey();
		$ak->load($akID);
		return $ak;
	}
	
	public static function getList() {
		return parent::getList('collection');	
	}
	
	/** 
	 * @access private 
	 */
	public function get($akID) {
		return CollectionAttributeKey::getByID($akID);
	}
	
	/** 
	 * Saves an attribute using its stock form.
	 */
	public function saveAttributeForm($nvc) {
		$this->saveAttribute($nvc);
	}
	
	/** 
	 * Sets an attribute directly with a passed value.
	 */
	public function setAttribute($nvc, $value) {
		$this->saveAttribute($nvc, $value);
	}
	
	protected function saveAttribute($nvc, $value = false) {
		// We check a cID/cvID/akID combo, and if that particular combination has an attribute value ID that
		// is NOT in use anywhere else on the same cID, cvID, akID combo, we use it (so we reuse IDs)
		// otherwise generate new IDs
		$av = $nvc->getAttributeValueObject($this, true);
		parent::saveAttribute($av, $value);
		$db = Loader::db();
		$v = array($nvc->getCollectionID(), $nvc->getVersionID(), $this->getAttributeKeyID(), $av->getAttributeValueID());
		$db->Replace('CollectionAttributeValues', array(
			'cID' => $nvc->getCollectionID(), 
			'cvID' => $nvc->getVersionID(), 
			'akID' => $this->getAttributeKeyID(), 
			'avID' => $av->getAttributeValueID()
		), array('cID', 'cvID', 'akID'));
	}
	
	public function add($akHandle, $akName, $akIsSearchable, $atID) {
		$ak = parent::add('collection', $akHandle, $akName, $akIsSearchable, $atID);
		return $ak;
	}

}

class CollectionAttributeValue extends AttributeValue {

	public function setCollection($cObj) {
		$this->c = $cObj;
	}
	
	public static function getByID($avID) {
		$cav = new CollectionAttributeValue();
		$cav->load($avID);
		if ($cav->getAttributeValueID() == $avID) {
			return $cav;
		}
	}

	public function delete() {
		parent::delete();
		$db = Loader::db();
		$db->Execute('delete from CollectionAttributeValues where cID = ? and cvID = ? and akID = ? and avID = ?', array(
			$this->c->getCollectionID(), 
			$this->c->getVersionID(),
			$this->attributeKey->getAttributeKeyID(),
			$this->getAttributeValueID()
		));
	}
}
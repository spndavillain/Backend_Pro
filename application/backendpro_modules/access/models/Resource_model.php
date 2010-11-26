<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * BackendPro
 *
 * A website backend system for developers for PHP 5.2.6 or newer
 *
 * @package         BackendPro
 * @author          Adam Price <adam@kaydoo.co.uk>
 * @copyright       2008-2010, Adam Price
 * @license         http://www.opensource.org/licenses/mit-license.php MIT
 * @license         http://www.gnu.org/licenses/gpl.html GPL
 * @link            http://www.kaydoo.co.uk/projects/backendpro
 * @filesource
 */

/**
 * The resource model provides functions to get/insert/update/delete access
 * resources from the database.
 *
 * @subpackage      Access Module
 */
class Resource_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();

        $tables = $this->config->item('tables','backendpro');

        // Set base model properties
        $this->table = $tables['resources'];

        log_message('debug','Resource_model loaded');
    }

    /**
     * Insert a resource
     * 
     * @param string $name Resource name
     * @param int $parent_id Parent resource ID
     * @return int
     */
    public function insert($name, $parent_id)
    {
        if(!is_int($parent_id))
        {
            show_error("Cannot insert resource, parent_id must be an int");
        }

        $this->load->model("access/nested_sets_model");
        $this->nested_sets_model->setControlParams($this->table);
        $this->nested_sets_model->setPrimaryKeyColumn($this->primary_key);

        // Find the parent node
        $parent_node = $this->nested_sets_model->getNodeFromId($parent_id);

        // Insert a new node as its child
        $this->nested_sets_model->appendNewChild($parent_node, array('name' => $name, 'parent_id' => $parent_id));

        return $this->db->insert_id();
    }

    /**
     * Update a resource
     *
     * @param int $id Resource ID
     * @param string $name Resource name
     * @return bool
     */
    public function update($id, $name)
    {
        return parent::update($id, array('name' => $name));
    }

    /**
     * Delete resource
     *
     * @param int $id Resource ID
     * @return void
     */
    public function delete($id)
    {
        if(!is_int($id))
        {
            show_error("Cannot delete resource, id must be an int");
        }

        $this->load->model("access/nested_sets_model");

        $this->nested_sets_model->setControlParams($this->table);
        $this->nested_sets_model->setPrimaryKeyColumn($this->primary_key);

        // Fetch the node we want to delete
        $node = $this->nested_sets_model->getNodeFromId($id);

        $this->nested_sets_model->deleteNode($node);
    }
}

 /* End of Resource_model.php */
 /* Location: ./application/backendpro_modules/access/models/Resource_model.php */
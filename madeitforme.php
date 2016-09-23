<?php
ini_set("display_errors", 1);
class Scaffold{
    // entry point
    function run($params){
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){
            switch($_POST['action']){
                case "scaffold":
                    $this->generateScaffold();
                    break;

                case "controller";
                    $this->generateController();
                    break;

                case "model";
                    $this->generateModel();
                    break;
            }
        }else{
            $html = new Html;
            echo $html->index();
        }
    }

    function generateController(){
        $template = new Template;
        $single_controller = $template->single_controller();

        $result = new StdClass;
        $result->action = $_POST['action'];
        $result->single_controller = $template->wrapper('Controller', $single_controller, 'php');

        echo json_encode($result);
    }

    function generateModel(){
        $template = new Template;
        $single_model = $template->single_model();

        $result = new StdClass;
        $result->action = $_POST['action'];
        $result->single_model = $template->wrapper('Model', $single_model, 'php');

        echo json_encode($result);
    }

    function generateScaffold(){
        $template = new Template;
        $sql = MySql::generate();
        $controller = $template->controller();
        $model = $template->model();
        $view_add = $template->view_form();
        $view_edit = $template->view_form(true);
        $view_index = $template->view_index();

        $result = new StdClass;
        $result->action = $_POST['action'];
        $result->sql = $template->wrapper("SQL", $sql, 'sql');
        $result->controller = $template->wrapper("Controller", $controller, 'php');
        $result->model = $template->wrapper("Model", $model, 'php');
        $result->view_index = $template->wrapper("view/index, view/show", $view_index, 'html');
        $result->view_add = $template->wrapper("view/add", $view_add, 'html');
        $result->view_edit = $template->wrapper("view/edit", $view_edit, 'html');

        echo json_encode($result);
    }
}

/***************************************
* Generate Mysql Create table statement
****************************************/
class MySql{
    static function generate(){
        $fields = explode(';', trim($_POST['resource_fields']));
        $table_name = $_POST['resource_name'];

        $table = "CREATE TABLE `$table_name`(\n";
        $table .= "`id` int NOT NULL AUTO_INCREMENT,\n";

        foreach($fields as $field){
            list($column, $datatype) = explode(':', $field);

            /**
            * CHECK IF LENGTH IS PRESENT
            * IF NOT PRESENT THEN SET TO DEFAULT LENGTH FOR GIVEN DATA TYPE.
            */
            // preg_match("/\(([0-9]{1,3})\)$/", $datatype, $matches);
            // if(count($matches) > 0){

            // }

            /**
            * NEXT REQUIREMENT WE PROBABLY NEED TO DECLARE
            * IS IT NULLABLE OR NOT
            * RIGHT NOW IT IS SET TO "NOT NULL"
            */
            $table .= "`$column` $datatype NOT NULL,\n";
        }

        $table .= "PRIMARY KEY (`id`)\n";
        $table .= ")ENGINE=InnoDB;";

        return $table;
    }
}

/****************************************************
* Template for model, view, controller.
*****************************************************/
class Template{
    function __construct(){
        $this->resource_name = $_POST['resource_name'];
        $this->controller_name = $this->pluralize(ucfirst($this->resource_name));
        $this->model_name = $this->resource_name."_model";
        $this->single_controller_name = $this->pluralize(ucfirst($_POST['controller_name']));
        $this->single_model_name = ucfirst($_POST['model_name']."_model");
    }

    /**
    * taken from: http://stackoverflow.com/a/16925755
    * Pluralizes a word if quantity is not one.
    *
    * @param string $singular Singular form of word\
    * @return string Pluralized word if quantity is not one, otherwise singular
    */
    function pluralize($singular) {
        if(strlen($singular) == 0) return false;
        $last_letter = strtolower($singular[strlen($singular)-1]);
        switch($last_letter) {
            case 'y':
                return substr($singular,0,-1).'ies';
            case 's':
                return $singular.'es';
            default:
                return $singular.'s';
        }
    }

    function wrapper($title, $content, $lang){
        $panel = '<div class="panel panel-warning"><div class="panel-heading">%s</div><div class="panel-body"><pre><code class="language-%s">%s</code></pre></div></div>';
        return sprintf($panel, $title, $lang, $content);
    }

    /**
    * Generate single and empty controller.
    */
    function single_controller(){
        $controller =<<<EOT
&lt;?php
class {$this->single_controller_name} extends CI_Controller{
    function __construct(){
        parent::__construct();
    }
    
    /**
    * list all {$this->single_controller_name}
    */
    function index(){

    }

    /**
    * show {$this->single_controller_name} detail
    * @param integer \$id
    */
    function show(){

    }
    
    /**
    * Add new {$this->single_controller_name}
    */
    function add(){

    }
    
    /**
    * edit {$this->single_controller_name}
    */
    function edit(){

    }
    
    /**
    * delete {$this->single_controller_name}
    */
    function delete(){

    }
}
EOT;
        return $controller;
    }

    /**
    * Generate single and empty model.
    */
    function single_model(){
        $model =<<<EOT
&lt;?php
class {$this->single_model_name} extends CI_Controller{

}
EOT;
        return $model;
    }

    /**
    * Generate controller for CRUD functionality
    */
    function controller(){
        $lower_name = strtolower($this->controller_name);
        $controller =<<<EOT
&lt;?php
class {$this->controller_name} extends CI_Controller{
    function __construct(){
        parent::__construct();
        \$this->load->model('{$this->model_name}');
    }
    
    /**
    * list all {$this->controller_name}
    */
    function index(){
        \$data['{$lower_name}'] = \$this->{$this->model_name}->get_all();
        \$this->load->view('{$this->resource_name}/index', \$data);
    }
    
    /**
    * show {$this->resource_name} detail
    * @param integer \$id
    */
    function show(\$id){
        \$data['{$this->resource_name}'] = \$this->{$this->model_name}->get_item(\$id);
        \$this->load->view('{$this->resource_name}/show', \$data);
    }

    /**
    * Add new {$this->resource_name}
    */
    function add(){
        if(\$this->input->post()){
            \$this->{$this->model_name}->save();
        }

        \$this->load->view('{$this->resource_name}/add');
    }
    
    /**
    * display form to edit {$this->resource_name} using GET method
    * save user when POST request is present
    * @param integer \$id
    */
    function edit(\$id){
        if(\$this->input->post()){
            \$this->{$this->model_name}->edit();
        }

        \$data['{$this->resource_name}'] = \$this->{$this->model_name}->get_item(\$id);
        \$this->load->view('{$this->resource_name}/edit', \$data);
    }
    
    /**
    * delete a {$this->resource_name}
    * @param integer \$id
    */
    function delete(\$id){
        \$this->{$this->model_name}->erase(\$id);
    }
}
EOT;
        return $controller;
    }


    /**
    * Generate model for CRUD functionality
    */
    function model(){
        $uper_model_name = ucfirst($this->model_name);

        $fields = explode(';', trim($_POST['resource_fields']));

        $validation = "";
        foreach($fields as $field){
            list($column, $datatype) = explode(':', $field);
            $validation .= "\$this->form_validation->set_rules('$column', '$column', 'required');\n\t";
        }

        $model =<<<EOT
&lt;?php
class {$uper_model_name} extends CI_Model{
    // table name
    private \$tabel = '{$this->resource_name}';

    // primary key of the table
    private \$pKey = 'id';

    // where user should be redirect to after form saved?
    // don't forget to change/fill it.
    private \$slug = '';

    /**
    * get all {$this->resource_name}.
    * @return array
    */
    function get_all(){
        return \$this->db->get(\$this->table);
    }

    /**
    * get {$this->resource_name} detail.
    * @return object
    */
    function get_item(\$id){
        \${$this->resource_name} = \$this->db->get_where(\$this->table, array(\$this->pKey=>\$id));
        if(\${$this->resource_name}->num_rows() > 0){
            return \${$this->resource_name}->row();
        }

        show_error('Invalid ID');
    }

    /**
    * create new {$this->resource_name}.
    * @return boolean|void
    */
    function save(){
        // you may want to edit the validation rules to match your need.
        {$validation}
        if(\$this->form_validation->run() === true){
            if(\$this->db->insert(\$this->table, \$this->input->post())){
                redirect(\$this->slug);
            }else{
                show_error('something went wrong!');
            }
        }
        return false;
    }
    
    /**
    * edit a {$this->resource_name}.
    * @return boolean|void
    */
    function edit(\$id){
        // you may want to edit the validation rules to match your need.
        {$validation}
        if(\$this->form_validation->run() === true){
            if(\$this->db->update(\$this->table, \$this->input->post(), array(\$this->pKey=>\$id))){
                redirect(\$this->slug);
            }else{
                show_error('something went wrong!');
            }
        }
        return false;
    }
    
    /**
    * delete {$this->resource_name}.
    * @return void
    */
    function erase(\$id){
        \$this->db->delete(\$this->table, array(\$this->pKey=>\$id));
        redirect(\$this->slug);
    }
}
EOT;
        return $model;
    }

    /**
    * Generate add and edit view form
    */
    function view_form($edit = false){
        $fields = explode(';', trim($_POST['resource_fields']));
        
        $form_fields = "<form action='' method='post'>\n";
        foreach($fields as $field){
            list($column, $datatype) = explode(':', $field);
            $dt = preg_replace("/\((.*)\)$/", "", $datatype);

            if(strtolower($dt) == "enum"){
                preg_match("/\(([a-zA-Z0-9\W]+)\)$/", $datatype, $matches);
                if(count($matches) == 0){
                    echo '{"error": "enum data type error. please check resource fields."}';
                    die();
                }

                $opts = explode(',', str_replace("'", "", $matches[1]));

                $form_fields .= "\t<select name='$column' id='$column'>\n";
                foreach($opts  as $opt){
                    $form_fields .= "\t\t<option value='$opt'".($edit ? "<?php echo \${$this->resource_name}->$column == '$opt' ? ' selected':''; ?>":"").">$opt</option>\n";
                }
                $form_fields .= "\t</select>\n";
            }else{
                $form_fields .= "\t<input type='text' name='$column' ".($edit ? "value='<?php echo \${$this->resource_name}->$column; ?>'":"").">\n";
            }
        }

        $form_fields .= "</form>";
        return htmlspecialchars($form_fields);
    }

    /**
    * Generate index view
    */
    function view_index(){
        $fields = explode(';', trim($_POST['resource_fields']));
        $lower_name = strtolower($this->controller_name);

        $table = "<table>\n";
        // table header
        $table .= "\t<tr>\n";
        foreach($fields as $field){
            list($column, $datatype) = explode(':', $field);
            $table .= "\t\t<th>$column</th>\n";
        }
        $table .= "\t</tr>\n";
        // table header

        // table body
        $table .= "\t<?php foreach(\${$lower_name}->result() as \${$this->resource_name}): ?>\n";
        $table .= "\t<tr>\n";
        foreach($fields as $field){
            list($column, $datatype) = explode(':', $field);
            $table .= "\t\t<td><?php echo \${$this->resource_name}->$column?></td>\n";
        }
        $table .= "\t</tr>\n";
        $table .= "\t<?php endforeach; ?>\n";

        $table .= "</table>";
        // table body
        return htmlspecialchars($table);
    }
}

/****************************************************
* a class that providing interface
*****************************************************/
class Html{
    function index(){
        $html =<<<EOT
<html>
    <head>
        <title>MadeItForMe -  Codeigniter Generator</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/monokai-sublime.min.css" />
        <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.0/jquery-migrate.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/sql.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/php.min.js"></script>
        <style>
            body{background: #eee}
            .container{padding-top: 10px; background:#fff}
            .panel-default{display: none}
            small{color: #999; font-weight: normal; font-style:italic;}
            .heading{margin-top:0;margin-bottom:10px}
        </style>
    </head>
    <body>
        <div class="container">
            <h2 class="heading"><b>Made It For Me</b>. <small>simple codeigniter code generator</small></h2>
            <form method="get" action="">
                <div class="panel panel-info">
                    <div class="panel-heading">What type of code should I made for You?</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="action" id="action" class="form-control">
                                    <option value="scaffold">Scaffold</option>
                                    <option value="controller">Controller</option>
                                    <option value="model">Model</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SCAFFOLD GENERATOR -->
                <div class="panel panel-default" id="scaffold-generator" style="display:block">
                    <div class="panel-heading">Scaffold Generator</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="">Resource Name <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-title="fill with your desired table name."></i></label>
                                    <input type="text" name="resource_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="">Fields <small>format are like: field_name:datatype(length),...filed_name:datatype(length). id will automatically added.</small></label>
                                    <input type="text" name="resource_fields" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- CONTROLLER GENERATOR -->
                <div class="panel panel-default" id="controller-generator">
                    <div class="panel-heading">Controller Generator</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="">Controller Name</label>
                                    <input type="text" name="controller_name" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- MODEL GENERATOR -->
                <div class="panel panel-default" id="model-generator">
                    <div class="panel-heading">Model Generator</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="">Model Name</label>
                                    <input type="text" name="model_name" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <a href="" id="made-it" class="btn btn-block btn-primary">Made it For me please!</a>
                    </div>
                </div>
            </form>
            <div class="row">
                <hr />
                <div class="col-md-12" id="result"></div>
                <div class="clearfix"></div>
                <hr />
                <div class="col-md-12" style="padding-bottom: 30px; text-align:center">
                    <p>&copy; 2016 - dariel87</p>
                    <p><small>made with heart and finger of course. hopefully this can be useful for anybody.</small></p>
                </div>
            </div>
        </div>
        <script>
            jQuery(function(){
                $('select').select2().on('change', function(){
                    var sel = $(this).val();
                    var el = $("#"+sel+"-generator");
                    
                    if(sel.length > 0 && !el.is(':visible')){
                        $(".panel-default").hide('slow');
                        el.show('slow');
                    }
                });

                $('[data-toggle=tooltip]').tooltip();

                $('#made-it').on('click', function(e){
                    e.preventDefault();

                    switch($('[name=action]').val()){
                        case "scaffold":
                            if($('[name=resource_name]').val().length == 0 || $('[name=resource_fields]').val().length == 0){
                                alert('Both "Resource Name" and "Fields" field are mandatory. aborting...');
                                return;
                            }
                            break;

                        case "controller":
                            if($('[name=controller_name]').val().length == 0){
                                alert('Please enter the controller name. aborting...');
                                return;
                            }
                            break;

                        case "model":
                            if($('[name=model_name]').val().length == 0){
                                alert('Please enter the model name. aborting...');
                                return;
                            }
                            break;
                    }

                    $.post('', $('form').serialize(), function(data){
                        $('#result').html('');

                        if(data.hasOwnProperty('error')){
                            alert(data.error);
                        }else{
                            if(data.action == 'scaffold'){
                                $('#result').append(data.sql);
                                $('#result').append(data.controller);
                                $('#result').append(data.model);
                                $('#result').append(data.view_index);
                                $('#result').append(data.view_add);
                                $('#result').append(data.view_edit);
                            }

                            if(data.action == 'controller'){
                                $('#result').append(data.single_controller);
                            }

                            if(data.action == 'model'){
                                $('#result').append(data.single_model);
                            }

                            $('code').each(function(i, block) {
                                hljs.highlightBlock(block);
                            });
                        }
                    }, 'json');
                });
            });
        </script>
    </body>
</html>
EOT;
        return $html;
    }
}

$scaffold = new Scaffold;
$scaffold->run($_GET);
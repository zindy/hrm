<?php
  // This file is part of the Huygens Remote Manager
  // Copyright and license notice: see license.txt

require_once( "User.inc.php" );
require_once( "Fileserver.inc.php" );


class OmeroConnection {

    /*!
      \var    $omeroTree    
      \brief  Stores the contents of the user's Omero tree.
    */
    private $omeroTree;

    /*!
      \var    $omeroUser
      \brief  Stores the Omero user name for logging purposes.
    */
    private $omeroUser;

    /*!
      \var    $omeroPass
      \brief  Stores the Omero user password for logging purposes.
    */
    private $omeroPass;

   /*!
      \var    $loggedIn
      \brief  Boolean to know whether the login was successful.
    */
    public $loggedIn;
    

        /* ----------------------- Constructor ---------------------------- */

    /*!
     \brief   Constructor
    */
    public function __construct( $omeroUser, $omeroPass ) {
        
        if ( !empty($omeroUser) ) {
            $this->omeroUser = $omeroUser;
        } else {
            return "Impossible to log on to your Omero account.
                    Please try again. ";
        }

        if ( !empty($omeroPass) ) {
            $this->omeroPass = $omeroPass;
        } else {
            return "Impossible to log on to your Omero account.
                    Please try again. ";
        }

        $this->checkOmeroCredentials();
    }

        /* -------------------- General Omero processes -------------------- */

    /*!
     \brief    From the login credentials provided by the user it attempts
               to establish communication with the Omero server.
    */
    private function checkOmeroCredentials() {

        $cmd = $this->buildCredentialsCmd();

            /* Authenticate against the Omero server. */
        $loggedIn = shell_exec($cmd);

        if ($loggedIn == NULL) {
            report("Attempt to log on to Omero server failed.", 1);
            return "Attempt to log on to Omero server failed.";
        }
        
            /* Check whether the attempt was successful. */
        if (strstr($loggedIn, '-1')) {
            $this->loggedIn = FALSE;
        } else {
            $this->loggedIn = TRUE;
        }
    }

    /*!
     \brief   Retrieves the Omero data tree as returned by the ome_hrm script.
     \return  The XML string with the Omero data tree.
    */
    private function getRawOmeroDataTree () {
        
        $cmd = $this->buildTreeCmd();

        $omeroData = shell_exec($cmd);
        if ($omeroData == NULL) {
            report("Retrieving Omero data failed.", 1);
            return "Retrieving Omero data failed.";
        }
        
            /* Filter out any Omero output that is not XML. */
        preg_match("/<(.*)/",$omeroData,$matches);
        $omeroData = "<" . end($matches);
        
        return $omeroData;
    }
    
    /*!
     \brief   Retrieves one image from the Omero server.
     \param   $postedParams Alias of $_POST with the user selection.
     \param   $fileServer Instance of the Fileserver class.
     \return  Ocassionally, an error message.
    */
    public function importImage($postedParams, $fileServer) {
        
        if (isset($postedParams['OmeImageName'])) {
            $imgName = basename($postedParams['OmeImageName']);
            $imgName = str_replace("Image: ","",$imgName);
        } else {
            return "No files selected.";
        }

        if (isset($postedParams['OmeImageId'])) {
            $imgId = $postedParams['OmeImageId'];
        } else {
            return "No files selected.";
        }
        
        $cmd = $this->buildImportCmd($imgName, $fileServer, $imgId);
        
        if (shell_exec($cmd) == NULL) {
            report("Importing image from Omero failed.", 1);
            return "Importing image from Omero failed.";
        }
    }

    /*!
     \brief   Attaches a deconvolved image to an Omero dataset.
     \param   $postedParams An alias of $_POST with names of selected files.
     \param   $fileServer   An instance of the Fileserver class.
     \return  Ocassionally an error message.
    */
    public function exportImage($postedParams, $fileServer) {

        if (isset($postedParams['selectedFiles'])) {
            $selectedFiles = explode(" ",trim($postedParams['selectedFiles']));
        } else {
            return "No files selected.";
        }

        if (isset($postedParams['OmeDatasetId'])) {
            $datasetId = $postedParams['OmeDatasetId'];
        } else {
            return "No destination dataset selected.";
        }
        
            /* Export all the selected files. */
        foreach ($selectedFiles as $file) {
            
            $cmd = $this->buildExportCmd($file, $fileServer, $datasetId);
            
            if (shell_exec($cmd) == NULL) {
                report("Exporting image to Omero failed.", 1);
                return "Exporting image to Omero failed.";
            }
        }
    }

        /* ---------------------- Command builders--------------------------- */

    /*!
     \brief   It builds an 'ome_hrm' (see script) compliant command
              to check whether the user can log on to Omero.
     \return  A string with the complete command.                       
    */
    private function buildCredentialsCmd() {

            /* See 'chechCredentials' command in file 'bin/ome_hrm'. */
        $cmd  = "bin/ome_hrm";
        $cmd .= " ";
        $cmd .= "checkCredentials";
        $cmd .= " ";
        $cmd .= $this->omeroUser;
        $cmd .= " ";
        $cmd .= $this->omeroPass;

        return $cmd;
    } 

    /*!
     \brief   It builds an 'ome_hrm' (see script) compliant command
              to retrieve the user's Omero data tree.
     \return  A string with the complete command.                       
    */
    private function buildTreeCmd() {

            /* See 'retrieveUserTree' command in file 'bin/ome_hrm'. */
        $cmd  = "bin/ome_hrm";
        $cmd .= " ";
        $cmd .= "retrieveUserTree";
        $cmd .= " ";
        $cmd .= $this->omeroUser;
        $cmd .= " ";
        $cmd .= $this->omeroPass;

        return $cmd;
    }   
    
    /*!
     \brief   It builds an 'ome_hrm' (see script) compliant command
              to export one image to the Omero server.
     \param   $file The name and relative path of the image to be exported.
     \param   $fileServer An instance of the Fileserver class.
     \param   $datasetId  The Omero ID of the dataset to export the image to.
     \return  A string with the complete command.                       
    */
    private function buildExportCmd($file, $fileServer, $datasetId) {

            /* $file may contain relative paths. Here the absolute path. */
        $fileAndPath = $fileServer->destinationFolder() . "/" . $file;

            /* See 'HRMToOmero' command in file 'bin/ome_hrm'. */
        $cmd  = "bin/ome_hrm";         
        $cmd .= " ";
        $cmd .= "HRMToOmero";          
        $cmd .= " ";
        $cmd .= $this->omeroUser;       
        $cmd .= " ";
        $cmd .= $this->omeroPass;      
        $cmd .= " ";
        $cmd .= $datasetId;            
        $cmd .= " ";
        $cmd .= $fileAndPath;          
        $cmd .= " ";
        $cmd .= $this->getOriginalName($file);
        $cmd .= " ";
        $cmd .= $this->getDeconParameterSummary($fileAndPath);

        return $cmd;
    }

    /*!
     \brief   It builds an 'ome_hrm' (see script) compliant command
              to import one image from the Omero server.
     \param   $imgName The name of the image in the Omero server.
     \param   $fileServer An instance of the Fileserver class.
     \param   $imgId The ID of the image in the Omero server.
     \return  A string with the complete command.                       
    */
    private function buildImportCmd($imgName, $fileServer, $imgId) {

        $fileAndPath = $fileServer->sourceFolder() . "/" . $imgName;

            /* See 'omeroToHRM' command in file 'bin/ome_hrm'. */
        $cmd  = "bin/ome_hrm";
        $cmd .= " ";
        $cmd .= "omeroToHRM ";
        $cmd .= " ";
        $cmd .= $this->omeroUser;
        $cmd .= " ";
        $cmd .= $this->omeroPass;
        $cmd .= " ";
        $cmd .= $imgId;
        $cmd .= " ";
        $cmd .= $fileAndPath;

        return $cmd;
    }

        /* ---------------------- Omero Tree Assemblers ------------------- */

    /*!
     \brief  Gets the last requested JSON version of the user's Omero tree.
     \return The string with the JSON information.
    */
    public function getLastOmeroTree() {

        if (!isset($this->omeroTree)) {
            $this->getUpdatedOmeroTree();
        }
        
        return $this->omeroTree;
    }

    /*!
     \brief  Gets an updated JSON version of the user's Omero tree.
     \return The string with the JSON information.
    */
    public function getUpdatedOmeroTree() {

        $omeroTree = array();
        
        $omeroData = $this->getRawOmeroDataTree();
        
        $pattern = "/<Project>(.*?)<\/Project>/";
        preg_match_all($pattern, $omeroData, $allProjects);

            /* Loop over the projects. */
        foreach ($allProjects[1] as $key => $project) {

                /* Get the project details. */
            $pattern = "/(.*?)<id>(.*?)<\/id>/";
            if (preg_match($pattern, $project, $projectInfo)) {

                    /* Look for project children. */
                $projectDatasets = $this->getProjectDatasets($project);

                    /* If the project has no children. */
                if (empty($projectDatasets)) {
                    $omeroTree[$key] = "Project: " . $projectInfo[1];
                    
                } else {
                    
                        /* Project name. */
                    $omeroTree[$key][(string) "label" ]
                        = (string) "Project: " . $projectInfo[1];

                        /* Project id. */
                    $omeroTree[$key][(string) "id" ] = $projectInfo[2];
                    
                        /* Children. */
                    $omeroTree[$key][(string) "children" ] = $projectDatasets;
                }
            }
        }

        $this->omeroTree = json_encode($omeroTree);

        return $this->omeroTree;
    }

    /*!
     \brief  Gets the Omero project info in a multidimensional array.
     \param  $project The XML string with the project information.
     \return The multidimensional array with the project information.
    */
    private function getProjectDatasets( $project ) {

        $projectDatasets = array();

            /* Get the project datasets. */
        $pattern = "/<Dataset>(.*?)<\/Dataset>/";
        preg_match_all($pattern, $project, $allDatasets);
        
            /* Loop over the datasets. */
        foreach ($allDatasets[1] as $key => $dataset) {
            
                /* Get the dataset details. */
            $pattern = "/(.*?)<id>(.*?)<\/id>/";
            if (preg_match($pattern, $dataset, $datasetInfo)) {
                
                    /* Look for dataset children. */
                $datasetImages = $this->getDatasetImages($dataset);

                    /* If the dataset has no children. */
                if (empty($datasetImages)) {
                    $projectDatasets[$key] = "Dataset: " . $datasetInfo[1];
                } else {

                        /* Dataset name. */
                    $projectDatasets[$key][(string) "label" ]
                        = "Dataset: " . $datasetInfo[1];

                        /* Dataset id. */
                    $projectDatasets[$key][(string) "id" ] = $datasetInfo[2];

                        /* Children. */
                    $projectDatasets[$key][(string) "children" ]
                        = $datasetImages;                    
                }
            }   
        }

        return $projectDatasets;
    }

    /*!
     \brief  Gets the Omero image of a dataset in a multidimensional array.
     \param  $dataset An XML string with the dataset information.
     \return The multidimensional array with the image names and their id's.
    */
    private function getDatasetImages( $dataset ) {

            /* Initizalize the array. */
        $datasetImages = array();
        

        $pattern = "/<Image>(.*?)<id>(.*?)<\/id>/";
        if (!preg_match_all($pattern, $dataset, $allImages)) {

                /* If the dataset contains no data we'll say so. */
            $datasetImages[0][(string) "id"]    = "-1";
            $datasetImages[0][(string) "label"] = "No archived data.";


        } else {
        
                /* If the dataset does contains images we'll loop over them. */
            foreach ($allImages[1] as $key => $imageName) {
                
                    /* Image name. */
                $datasetImages[$key][(string) "label"] = "Image: " . $imageName;
                
                    /* The image 'id' is located in a sub-array within
                     'allImages', which can be accessed with the current 'key'.*/
                $datasetImages[$key][(string) "id"] = $allImages[2][$key];
            }
        }

        return $datasetImages;
    }

        /* ------------------------- Parsers ------------------------------ */
    /*!
     \brief   Parses the HRM job parameters (html) file into a plain string
              to be used as Omero annotation.
     \param   $file The path and file name of the HRM deconvolution result.
     \return  The plain string with the parameter summary.
    */
    private function getDeconParameterSummary($file) {

            /* A summary title. */
        $summary  = "'[Report of deconvolution parameters from the ";
        $summary .= "Huygens Remote Manager for file ";
        $summary .= basename($file) . " ]: ";

            /* Get the parameter summary (HTML text) of the HRM job. */
        $extension      = pathinfo($file, PATHINFO_EXTENSION);
        $parametersFile = str_replace($extension,"parameters.txt",$file);
        $parameters     = file_get_contents($parametersFile);

        if (!$parameters) {
            $summary .= "Parameters not available.'";
            return $summary;
        }

            /* Loop over the parameter tables. */
        $parameterSets = explode("<table>",$parameters);
        foreach ($parameterSets as $key => $parameterSet) {

                /* Irrelevant information. */
            if ($key == 0) {
                continue;
            }

                /* Loop over the table rows. */
            $rows = explode("<tr>",$parameterSet);
            foreach ($rows as $key => $row) {

                    /* Irrelevant information. */
                if ($key == 1 || $key == 2) {        
                    continue;
                }

                    /* Loop over the row columns. */
                $columns = explode("<td",$row);
                foreach ($columns as $key => $column) {

                        /* Irrelevant information. */
                    if ($key == 3) {
                        continue;
                    }
                    
                    $column = strip_tags($column);
                    $column = explode(">",$column);
                    
                    if (isset($column[1])) {
                        if ($key == 1) {
                            $summary .=
                                str_replace("(&mu;m)","(mu)",$column[1]);
                        }
                        
                        if ($key == 2) {
                            $summary .=
                                " (ch. " . strtolower($column[1]) . "): ";
                        }
                        
                        if ($key == 4) {
                            $summary .= $column[1] . " | ";
                        }
                    }
                }
            }    
        }

        return $summary . "'";
    }

    /*!
     \brief   Removes the deconvolution suffix to find the original file name.
     \param   The name of the deconvolved dataset.
     \return  The name of the raw dataset.
    */
    private function getOriginalName($file) {

            /* Remove any relative paths that may exist. */
        $file = pathinfo($file, PATHINFO_BASENAME);

            /* Remove the HRM deconvolution suffix and file extension. */
        $replaceThis  = "/_([a-z0-9]{13,13})_hrm\.(.*)$/";
        $replaceWith  = "";
        $originalName = preg_replace($replaceThis,$replaceWith,$file);

            /* In case of error just return the name of the deconvolved file. */
        if ($originalName != NULL) {
            return $originalName;
        } else {
            return $file;
        }
    }
    
}



?>
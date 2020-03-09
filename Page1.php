<!DOCTYPE html>

<html lang = "fr">
  <head>
    <meta charset= "utf-8">
    <title>Page Reentrante</title>
  </head>
  <body>
    <?php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $dbname = "commerce";
     $conn = "";

     try{
        $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);

        # Set the PDO error mode to exception
        $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $insert_data_array = array("NUM_CLIENT" => 14);

        $client_statment = $conn -> query(query_select("client", "*"));
        $client_statment -> execute();
        $client_array = $client_statment -> fetchAll(PDO::FETCH_ASSOC);

        $commande_statment = $conn -> prepare(query_select("commande", "*"));
        $commande_statment -> execute();
        $commande_array = $commande_statment -> fetchAll(PDO::FETCH_ASSOC);

        $detail_statment = $conn -> prepare(query_select("detail", "*"));
        $detail_statment -> execute();
        $detail_array = $detail_statment -> fetchAll(PDO::FETCH_ASSOC);

        generate_select($client_array, $commande_array, $detail_array);

        if(array_key_exists('submit', $_POST)){
            $num_cli_stmt = $conn -> prepare("SELECT NumClient from client WHERE Nom = "."'".$_POST['Nom']."';");
            $num_cli_stmt -> execute();
            $num_cli_array = $num_cli_stmt -> fetch(PDO::FETCH_ASSOC);

            $cmd_array = array();
            foreach($num_cli_array as $key => $value){
               if($value != null && $value != "" && $key == "NumClient"){
                  $cmd_stmt = $conn -> prepare("SELECT * from commande WHERE NumClient = "."'".$value."';");
                  $cmd_stmt -> execute();
                  $result_array = $cmd_stmt -> fetchAll(PDO::FETCH_ASSOC);
               }
            }

            print_results($result_array);
        }
    }
      # Error message in case of failure of connection to the database
     catch(PDOException $e){
         echo "Connection failed: ".$e -> getMessage();
     }


     # Print the result of the command research
     function print_results($array){
        echo("<table style='border: 1px solid #333'>
               <thead style='border: 1px solid #333'>
                  <tr>
                     <th>NumCommande</th>
                     <th>NumClient</th>
                     <th>DateCom</th>
                  </tr>
               </thead>
               <tbody>");
        foreach ($array as $value_arr) {
            echo("
                  <tr>
                     <td>".$value_arr['NumCom']."</td>
                     <td>".$value_arr['NumClient']."</td>
                     <td>".$value_arr['DateCom']."</td>
                  </tr>
               ");
        }
        echo("</tbody></table>");
     }


     # Generate the select and its options by querying the database
     function generate_select($client_array, $commande_array, $detail_array){
         echo("
         <form id='myForm' action='' method='post'>
            <select id = 'Nom' name='Nom'><option>Nom</option>".generate_option($client_array, "Nom")."</select>".
            "<select id = 'Localité' name='Localité'><option>Localité</option>".generate_option($client_array, "Localité")."</select>".
            "<select id = 'DateCom' name='DateCom'><option>DateCom</option>".generate_option($commande_array, "DateCom")."</select>".
            "<select id = 'Produit' name='Produit'><option>Produit</option>".generate_option($detail_array, "NumProd")."</select>
            <button type='submit' name='submit' value='submit'>Submit</button>
         </form>"
         );
     }


     # Generate options from an array of data, fetch data from the given key
     function generate_option($array, $req_key){
         $outpout_string = "";

         foreach($array as $value_arr){
            foreach($value_arr as $key => $value){
               if($value != null && $value != "" && $key == $req_key){
                $outpout_string = $outpout_string."<option value=".$value.">".$value."</option>";
               }
            }
         }
         return $outpout_string;
     }


     # Return a string containing a SQL request to the given table
     function query_select($table, $row){
        $req = null;

         if(gettype($table) == "string" && gettype($row) == "string"){
            $req = "Select ".$row." from ".$table;
         }

        return $req;
     }


     # Return a string containing a SQL request to delete the given table at the given condition (where $attribute = $condition_value)
     function query_delete($table, $attribute, $value){
         if(gettype($table) == "string" && gettype($value) == "string" && gettype($attribute) == "string"){
          return "Delete from ".$table." where ".$attribute." = '".$value."'";
         }
         else {
          return null;
         }
     }


     # Return a string containing a SQL request to insert data in the given table
     # Take an associative array as $data_array. the $key correspond to the attribute and the $value to the data itself
     # The array can also be sequential (non-associative), in that case every cell of the array will be considered a data,
     # and will be stored in the next attribute of the table
     function query_insert($table, $data_array){
         if(gettype($table) == "string" && gettype($data_array) == "array"){

            $i = 0;
            $array_len = count($data_array);
            $req_str = "insert into ".$table;

            # In case the array is associative, add the list of attributes to the request
            if(is_array_assosiative($data_array)){
               $req_str = $req_str." (";
               foreach ($data_array as $key => $value){
                 $req_str = $req_str.$key;
                 $i++;
                 if($i < $array_len){
                    $req_str = $req_str.", ";
                 }
               }
               $i = 0;
               $req_str = $req_str.") \n";
            }

            # Add the list of data itself to the request
            $req_str = $req_str." values(";
            foreach ($data_array as $key => $value){
               if (gettype($value) == "string"){
                   $req_str = $req_str."'".$value."'";
               } else {
                   $req_str = $req_str.$value;
             }

               $i++;
               if($i < $array_len){
                 $req_str = $req_str.", ";
               }
            }
            return $req_str.")";
         }
         else {
          return null;
         }
     }


     # Return a string containing a SQL request to modify the given table at the given condition (where $attribute = $condition_value)
     # Take an associative array as $date_array. the $key correspond to the attribute and the $value to the data itself
     function query_update($table, $data_array, $attribute, $condition_value){
         if(gettype($table) == "string" && gettype($data_array) == "array"){

            $req_str = "update ".$table." set ";
            foreach ($data_array as $key => $value){
             if($value == "string"){
                 $req_str = $req_str.$key." = '".$value."'";
             } else{
                 $req_str = $req_str.$key." = ".$value;
             }
            }
            $req_str." where ".$attribute." = ".$condition_value;

            return $req_str;
         }
         else {
          return null;
         }
     }


     # Return true if the array is associative, false if not
     function is_array_assosiative($arr){
          return (array_keys($arr) !== range(0, count($arr) - 1));
      }
     ?>
    <div id="result"></div>
  </body>
</html>

<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

if (!function_exists('get_safe_table_name')) {
    function get_safe_table_name($con, $prefix, $id) {
        if (empty($id)) return "";
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        $table_pattern = $prefix . $id . "%";
        $query = "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $table_pattern) . "'";
        $result = mysqli_query($con, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            return $row[0];
        }
        return "";
    }
}

$id = isset($_POST['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']) : '';
$tablename = get_safe_table_name($con, "fci_leg1_", $id);
if (empty($tablename)) {
    $tablename = "fci_leg1_".$id;
}
$tablename1 = $tablename;
$leg = 1;

?>
<style>
     td {
            font-size: 16px; /* Increase font size for table headers and data cells */
        }
        .table thead tr th {
    background-color: #95b75d !important;
    /* border: 2px solid #777; */
    color: black;
    /* Optional: Font size for table header */
}
    </style>

                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="Warehouse.php">Home</a></li>
                    <li class="active">FCI View</li>
                </ul>
                <!-- END BREADCRUMB -->


				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
							<div class="panel-heading">
                                    <h3 class="panel-title">FCI</h3>
                                </div>
								<div style="float:right" style="margin:10px">
									<button id="downloadCSV" class="btn btn-warning" style="margin-bottom: 10px;" type="button">Download CSV</button>
									<button id="downloadXLSX" class="btn btn-success" style="margin-bottom: 10px;" type="button">Download XLSX</button>
								</div>
                                <div class="panel-body">
                                 <div class="table-responsive">
                                    <table id="export_table" class="table datatable">
                                        <thead>
                                            <tr>
												<th style="font-size:15px">District</th>
												<th style="font-size:15px">Name of FCI</th>
												<th style="font-size:15px">FCI ID</th> 
												<th style="font-size:15px">Warehouse Type</th>
												<th style="font-size:15px">Latitude</th>
												<th style="font-size:15px">Longitude</th>
												<th style="font-size:15px">Offtake(Qtl)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
										<?php
										$chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tablename) . "'");
								if ($chk && mysqli_num_rows($chk) > 0) {
									$query = "SELECT * FROM " . mysqli_real_escape_string($con, $tablename) . " WHERE 1";
									$result = mysqli_query($con,$query);
									if($result){
										while($row = mysqli_fetch_array($result))
										{
											echo "<tr><td>{$row['district']}</td>".
											"<td>{$row['name']}</td>".
											"<td>{$row['id']}</td>".
											"<td>{$row['warehousetype']}</td>".
											"<td>{$row['latitude']}</td>".
											"<td>{$row['longitude']}</td>".
											"<td>{$row['offtake']}</td></tr>";
										}
									}
								}
								?>
                                        </tbody>
                                    </table>
                                  </div>
                                </div>
                            </div>
                            <!-- END SIMPLE DATATABLE -->

                        </div>
                    </div>

                </div>
                <!-- PAGE CONTENT WRAPPER -->
            </div>
            <!-- END PAGE CONTENT -->
        </div>
        <!-- END PAGE CONTAINER -->



    <!-- START SCRIPTS -->
        <!-- START PLUGINS -->
        <script type="text/javascript" src="js/plugins/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="js/plugins/jquery/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/plugins/bootstrap/bootstrap.min.js"></script>
        <!-- END PLUGINS -->

        <!-- THIS PAGE PLUGINS -->
        <script type='text/javascript' src='js/plugins/icheck/icheck.min.js'></script>
        <script type="text/javascript" src="js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
        <script type="text/javascript" src="js/plugins/datatables/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/tableExport.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jquery.base64.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/html2canvas.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/sprintf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/jspdf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/base64.js"></script>
		
		
        <script type="text/javascript" src="js/plugins.js"></script>
        <script type="text/javascript" src="js/actions.js"></script>
        <!-- END PAGE PLUGINS -->

        <!-- START TEMPLATE -->
       
        <!-- END TEMPLATE -->
		
		<script>
		function getDateString(){
			var currentDate = new Date();
			var year = currentDate.getFullYear();
			var month = currentDate.getMonth() + 1; // Month is zero-based, so we add 1
			var day = currentDate.getDate();
			var str = year + "-" + month + "-" + day;
			return str;
		}
		
		document.getElementById('downloadCSV').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				var tableName1 = '<?php echo $tablename1 ?>';
				const csvResponse = await fetch('api/DownloadOptimalDataFCILeg1.php?format=csv&tableName='+tableName+'&tableName1='+tableName1);
				const csvBlob = await csvResponse.blob();
				downloadFile(csvBlob, 'Mizoram_FCI_' + getDateString() + '.csv');
			} catch (error) {
				console.error('Error downloading CSV file:', error);
			}
		});

		// Event listener for downloading XLSX
		document.getElementById('downloadXLSX').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				var tableName1 = '<?php echo $tablename1 ?>';
				const excelResponse = await fetch('api/DownloadOptimalDataFCILeg1.php?format=xlsx&tableName='+tableName+'&tableName1='+tableName1);
				const excelBlob = await excelResponse.blob();
				downloadFile(excelBlob, 'Mizoram_FCI_' + getDateString() + '.xlsx');
			} catch (error) {
				console.error('Error downloading XLSX file:', error);
			}
		});
		
		// Functions for file download and PDF generation (similar to previous code)
		function downloadFile(blob, fileName) {
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = fileName;
			link.click();
			window.URL.revokeObjectURL(url);
		}


		</script>
    </body>
</html>

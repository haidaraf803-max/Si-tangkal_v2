<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <meta name="description" content="Sistem Informasi Terpadu Lingkungan dan Alam Kota Cimahi">
 
  <title>SI-TANGKAL KOTA CIMAHI</title>
  
  <link rel="icon" type="image/png" href="assets/img/cimahi.ico"/>
  <!-- Bootstrap - For testing only -->
  <!--link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"-->

  <!-- Calcite Maps Bootstrap -->
  <link rel="stylesheet" href="https://esri.github.io/calcite-maps/dist/css/calcite-maps-bootstrap.min-v0.10.css">
  
  <!-- Calcite Maps -->
  <link rel="stylesheet" href="https://esri.github.io/calcite-maps/dist/css/calcite-maps-arcgis-4.x.min-v0.10.css">

  <!-- ArcGIS JS 4 -->
  <link rel="stylesheet" href="https://js.arcgis.com/4.12/esri/css/main.css">
  
  <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>-->

  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      height: 100%;
      width: 100%;
    }
  </style>
  
  <script type="text/javascript">
	
	function onLoad() {
		var usertype = window.localStorage.getItem('username');
		if (usertype == null) {
			document.getElementById("menuEditor").style.display = "none";
		} else {
			document.getElementById("menuEditor").style.display = "block";
		}
	}
	
	function logout() {
		var txt;
		var r = confirm("Are you sure logout ?");
		if (r == true) {
			window.localStorage.clear();
			location.assign("index.html");
		} 
		/*else {
			txt = "You pressed Cancel!";
		}*/
	}
  </script>
  
</head>

<body class="calcite-maps calcite-nav-top" onload="onLoad();">
  <!-- Navbar -->

  <nav class="navbar calcite-navbar navbar-fixed-top calcite-text-light calcite-bg-dark calcite-bgcolor-dark-blue">
    <!-- Menu -->
    <div class="dropdown calcite-dropdown calcite-text-dark calcite-bg-light" role="presentation">
      <a class="dropdown-toggle" role="menubutton" aria-haspopup="true" aria-expanded="false" tabindex="0">
        <div class="calcite-dropdown-toggle">
          <span class="sr-only">Toggle dropdown menu</span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </a>
      <ul class="dropdown-menu" role="menu">
		<li><a role="menuitem" tabindex="0" href="#" data-target="#panelBasemaps" aria-haspopup="true"><span class="glyphicon glyphicon-th-large"></span> Basemaps</a></li>
        <li><a role="menuitem" tabindex="0" href="#" data-target="#panelLayer" aria-haspopup="true"><span class="glyphicon glyphicon-th-list"></span> Layer</a></li>
        <li><a role="menuitem" tabindex="0" href="#" data-target="#panelLegend" aria-haspopup="true"><span class="glyphicon glyphicon-list-alt"></span> Legend</a></li>
        <li><a role="menuitem" tabindex="0" href="#" data-target="#panelFind" aria-haspopup="true"><span class="glyphicon glyphicon-search"></span> Find</a></li>
        <li><a role="menuitem" tabindex="0" href="#" data-target="#panelEditor" aria-haspopup="true" id="menuEditor"><span class="glyphicon glyphicon-edit"></span> Editor</a></li>
        <li><a role="menuitem" tabindex="0" href="#" data-target="#panelPrint" aria-haspopup="true"><span class="glyphicon glyphicon-print"></span> Print</a></li>
        <li><a role="menuitem" tabindex="0" href="#" id="calciteToggleNavbar" aria-haspopup="true"><span class="glyphicon glyphicon-fullscreen"></span> Full Map</a></li>
        <!--<li><a role="menuitem" tabindex="0" href="#" data-target="#panelInfo" aria-haspopup="true"><span class="glyphicon glyphicon-info-sign"></span> About</a></li>
        <li><a role="menuitem" tabindex="0" href="contact.html" data-target="#panelLogout" data-toggle="modal" aria-haspopup="false"><span class="glyphicon glyphicon-envelope"></span> Contact</a></li>
        <li><a role="menuitem" tabindex="0" href="#" onclick="logout();" aria-haspopup="true"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>-->
      </ul>
    </div>
    <!-- Title -->
    <div class="calcite-title calcite-overflow-hidden">
      <span class="calcite-title-main">Si-TANGKAL</span>
      <span class="calcite-title-divider hidden-xs"></span>
      <span class="calcite-title-sub hidden-xs">Sistem Informasi Terpadu Lingkungan dan Alam Kota Cimahi</span>
    </div>
    <!-- Nav -->
    <!--<ul class="nav navbar-nav calcite-nav">
      <li>
        <div class="calcite-navbar-search calcite-search-expander">
          <div id="searchWidgetDiv"></div>
        </div>
      </li>
    </ul>-->
  </nav>

  <!--/.calcite-navbar -->

	<!-- Panel Logout -->
	
	<div id="panelLogout" class="modal fade" role="dialog" tabindex="-1">
	  <div class="modal-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header">
			<button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">x</span></button>
			<h4 class="modal-title">Confirmation</h4>
		  </div>
		  <div class="modal-body">
			<p>Are you sure for logout ?</p>
		  </div>
		  <div class="modal-footer">
			<button class="btn btn-default" data-dismiss="modal" type="button btn-primary">Cancel</button> <button class="btn btn-primary" type="button"> Yes </button>
		  </div>
		</div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	
	<div class="modal fade" id="modalSplash" tabindex="-1" role="dialog" aria-labelledby="splashlModal">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="splash-body">
                <div class="text-center">
                  <h3>Welcome to Calcite Maps</h3>
                  <hr>
                  <p>Use this application to interactively design your application and to explore the different colors, styles and layouts. When you are done, apply the CSS styles and classes to your own apps. Get a jump-start by starting with one of the <a href="../index.html">sample applications</a>.</p>
                  <br>
                  <div class="form-inline">
                    <div class="form-group">
                      <button type="button" class="btn btn-success btn-lg"  data-dismiss="modal">Get started</a>
                    </div>
                  </div>
                  <br>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Map  -->

  <div class="calcite-map calcite-map-absolute">
    <div id="mapViewDiv"></div>
  </div>

  <!-- /.calcite-map -->

  <!-- Panels -->

  <div class="calcite-panels calcite-panels-right calcite-bg-custom calcite-text-light calcite-bgcolor-dark-blue panel-group">

    <!-- Panel - Info -->

    <div id="panelInfo" class="panel collapse">
      <div id="headingInfo" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseInfo"  aria-expanded="true" aria-controls="collapseInfo"><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span><span class="panel-label">About</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelInfo"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a>  
        </div>
      </div>
      <div id="collapseInfo" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingInfo">
        <div class="panel-body">
          <table border="0"><tr><td>
		  <img src="assets/img/logo_2.png" width="90px" height="90px">
		  </td><td>&nbsp;&nbsp;</td><td>
          <p><b>SiJanTan v1.0</b><br><br>Sistem Informasi Pemeliharaan Jalan & Jembatan<br>Kota Balikpapan</p>
		  </td></tr>
		  </table>
        </div>
     </div>
    </div>
	
    <div id="panelContact" class="panel collapse">
      <div id="headingInfo" class="panel-heading" role="modal">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseContact"  aria-expanded="true" aria-controls="collapseContact"><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span><span class="panel-label">Contact</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelContact"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a>  
        </div>
      </div>
      <div id="collapseContact" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingContact">
        <div class="panel-body">
		  <p><b>SiJanTan v1.0</b><br><br>Sistem Informasi Jalan & Jembatan<br>Kota Balikpapan</p>
        </div>
     </div>
    </div>
	
    <!-- Panel - Legend -->

    <div id="panelLegend" class="panel collapse">
      <div id="headingLegend" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseLegend" aria-expanded="false" aria-controls="collapseLegend"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span><span class="panel-label">Legend</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelLegend"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapseLegend" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingLegend">
        <div class="panel-body">            
          <div id="legendDiv"></div>
        </div>
      </div>
    </div>
	
	<!-- Panel - Layer -->

    <div id="panelLayer" class="panel collapse">
      <div id="headingLayer" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseLayer" aria-expanded="false" aria-controls="collapseLegend"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span><span class="panel-label">Layer</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelLayer"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapseLayer" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingLayer">
        <div class="panel-body">            
          <div id="layerDiv"></div>
        </div>
      </div>
    </div>

	<!-- Panel - Editor -->

    <div id="panelEditor" class="panel collapse">
      <div id="headingLayer" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseEditor" aria-expanded="false" aria-controls="collapseEditor"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span><span class="panel-label">Editor</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelEditor"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapseLayer" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingLayer">
        <div class="panel-body">            
          <div id="editorDiv"></div>
        </div>
      </div>
    </div>

    <!-- Panel - Go To Center -->

    <!--<div id="panelcenter" class="panel collapse">
      <div id="headingcenter" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapsecenter" aria-expanded="false" aria-controls="collapsecenter"><span class="glyphicon glyphicon-search" aria-hidden="true"></span><span class="panel-label">Go To Center</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelcenter"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapsecenter" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingcenter">
        <div class="panel-body">            
          <div id="centerDiv">
			<div class="form-group>
				<label for="thing1" class="control-label">Pilih Nama Kereta :</label>
				<select name="goto" class="form-control">
					<option value="Kereta 1" data-nav="navbar-fixed-top" selected>Kereta 1</option>
					<option value="Kereta 2" data-nav="navbar-fixed-top">Kereta 2</option>
					<option value="Kereta 3" data-nav="navbar-fixed-top">Kereta 3</option>
					<option value="Kereta 4" data-nav="navbar-fixed-top">Kereta 4</option>
					<option value="Kereta 5" data-nav="navbar-fixed-top">Kereta 5</option>
				</select><br />
			</div>
			<button type="button" class="btn btn-primary" id="startBtn">Start</button>
			<button type="button" class="btn btn-primary" id="stopBtn">Stop</button>
		  </div>
        </div>
      </div>
    </div>-->
	
    <!-- Panel - Find -->

    <div id="panelFind" class="panel collapse">
      <div id="headingFind" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapseFind" aria-expanded="false" aria-controls="collapseFind"><span class="glyphicon glyphicon-search" aria-hidden="true"></span><span class="panel-label">Find</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelFind"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapseFind" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFind">
        <div class="panel-body">            
          <div id="FindDiv">
			<div class="form-group">
				<label for="thing1" class="control-label">Masukan Nama Jalan :</label>
				<input type="text" class="form-control" id="textSearch" value="" placeholder="Nama Jalan" />				
			</div>
			<button type="button" class="btn btn-primary" id="findBtn" />Find</button>
			<button type="button" class="btn btn-primary" id="clearBtn" />Clear</button>
			<br />
		  </div><br />
		  <p><b><span id="printResults"></span></b></p>
		  <table class="table table-hover" id="tbl"></table>
        </div>
      </div>
    </div>
	
	<!-- Panel - Layer -->

	<!-- Panel - Print -->

    <div id="panelPrint" class="panel collapse">
      <div id="headingPrint" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle" role="button" data-toggle="collapse" href="#collapsePrint" aria-expanded="false" aria-controls="collapseLegend"><span class="glyphicon glyphicon-print" aria-hidden="true"></span><span class="panel-label">Print</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelPrint"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a> 
        </div>
      </div>
      <div id="collapsePrint" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingPrint">
        <div class="panel-body">            
          <div id="printDiv"></div>
        </div>
      </div>
    </div>

	<!-- Panel Basemaps -->
    
    <div id="panelBasemaps" class="panel collapse"> 
      <div id="headingBasemaps" class="panel-heading" role="tab">
        <div class="panel-title">
          <a class="panel-toggle collapsed" role="button" data-toggle="collapse" href="#collapseBasemaps" aria-expanded="false"   aria-controls="collapseBasemaps"><span class="glyphicon glyphicon-th-large" aria-hidden="true"></span><span class="panel-label">Basemaps</span></a> 
          <a class="panel-close" role="button" data-toggle="collapse" tabindex="0" href="#panelBasemaps"><span class="esri-icon esri-icon-close" aria-hidden="true"></span></a>  
        </div>
      </div>
      <div id="collapseBasemaps" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingBasemaps">
        <div class="panel-body">
          <div id="basemapDiv"></div>
        </div>
      </div>
    </div>
	
  </div>

  <!-- /.calcite-panels -->

  <script type="text/javascript">
    var dojoConfig = {
      packages: [{
        name: "bootstrap",
        location: "https://esri.github.io/calcite-maps/dist/vendor/dojo-bootstrap"
      },
      {
        name: "calcite-maps",
        location: "https://esri.github.io/calcite-maps/dist/js/dojo"
      }]
    };
  </script>

  <!-- ArcGIS JS 4 -->
  <script src="https://js.arcgis.com/4.12/"></script>

  <script type="text/javascript" src="js/viewer_rth.js"></script>

</body>
</html>
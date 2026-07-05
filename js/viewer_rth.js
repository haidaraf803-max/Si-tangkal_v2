require([
      // ArcGIS
	  "esri/Map",
      "esri/WebMap",
      "esri/views/MapView",
	  "esri/layers/FeatureLayer",
	  "esri/tasks/FindTask",	  
	  "esri/tasks/support/Query",
      "esri/tasks/support/FindParameters",
	  "esri/core/watchUtils",
	  "esri/layers/GraphicsLayer",
	  "esri/Graphic",
	  "esri/layers/GroupLayer",

      // Widgets
      "esri/widgets/Home",
      "esri/widgets/Zoom",
      "esri/widgets/Compass",
      "esri/widgets/Search",
      "esri/widgets/Legend",
      "esri/widgets/BasemapToggle",
	  "esri/widgets/BasemapGallery",
      "esri/widgets/ScaleBar",
      "esri/widgets/Attribution",
	  "esri/widgets/LayerList",
	  "esri/widgets/Print",
	  "esri/widgets/Editor",
	  "esri/widgets/Locate",

      // Bootstrap
      "bootstrap/Collapse",
      "bootstrap/Dropdown",

      // Calcite Maps
      "calcite-maps/calcitemaps-v0.10",
      // Calcite Maps ArcGIS Support
      "calcite-maps/calcitemaps-arcgis-support-v0.10",

	  "dojo/query",
	  "dojo/aspect",
      "dojo/domReady!"
    ], function(Map, WebMap, MapView, FeatureLayer, FindTask, Query, FindParameters, watchUtils, GraphicsLayer, Graphic, GroupLayer, Home, Zoom, Compass, Search, Legend, BasemapToggle, BasemapGallery, ScaleBar, Attribution, LayerList, Print, Editor, Locate, Collapse, Dropdown, CalciteMaps, CalciteMapArcGISSupport, query, aspect) {

      /******************************************************************
       *
       * Create the map, view and widgets
       * 
       ******************************************************************/     
	  
		var trailheadsLabels = {
			symbol: {
			  type: "text",
			  color: "#000000",
			  haloColor: "#7F92FF",
			  haloSize: "1px",
			  font: {
				size: "12px",
				family: "Noto Sans",
				style: "italic",
				weight: "normal"
			  }
			},
			labelPlacement: "above-center",
			labelExpressionInfo: {
				expression: "$feature.TOPONIM"
			}
		};	 
		
		function getTitle(feature) {
			return feature.graphic.layer.title;
		}
	  
		var template = {
			title: "Jembatan",
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "Nama",
					label: "Nama Jembatan"
				},{
					fieldName: "TOPONIM",
					label: "Nama Jalan"
				},{
					fieldName: "Kab_Kota",
					label: "Kab/Kota"
				},{
					fieldName: "Jmlh_Btng",
					label: "Jumlah Bentang"
				},{
					fieldName: "Nilai_BA",
					label: "Nilai BA"
				},{
					fieldName: "Nilai_LNT",
					label: "Nilai LNT"
				},{
					fieldName: "Nilai_BB",
					label: "Nilai BB"
				},{
					fieldName: "Nilai_DAS",
					label: "Nilai DAS"
				},{
					fieldName: "Nilai_JBT",
					label: "Nilai JBT"
				},{
					fieldName: "Penanganan",
					label: "Penanganan"
				},{
					fieldName: "Kls_Jmbt",
					label: "Kelas Jembatan"
				},{
					fieldName: "Panjang",
					label: "Panjang"
				},{
					fieldName: "L_Jembatan",
					label: "Lebar Jembatan"
				},{
					fieldName: "L_Jalan",
					label: "Lebar Jalan"
				},{
					fieldName: "No_Jmbt",
					label: "No Jembatan"
				}]
			}],
			actions: [{
				title: " Dokumentasi",
				id: "view-Jembatan",
				className: "esri-icon-review"
			}]
		}

		var url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/1";
		var jbt = new FeatureLayer({url,
			title: "Jembatan",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});
		jbt.popupTemplate = template;
	  
		var imgUrl = "http://www.kav-32.com/silaja/foto/";	  
		template = {
			title: "Jalan",
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "TOPONIM",
					label: "Nama Jalan"
				},
				{
					fieldName: "Tahun",
					label: "Tahun"
				},
				{
					fieldName: "Fungsi_Jln",
					label: "Fungsi Jalan"			
				},
				{
					fieldName: "Status_Jln",
					label: "Status Jalan"			
				},
				{
					fieldName: "No_Ruas",
					label: "No Ruas"			
				},
				{
					fieldName: "Panjang_m",
					label: "Panjang (m)"			
				},
				{
					fieldName: "Lebar_m",
					label: "Lebar (m)"			
				},
				{
					fieldName: "Kondsi_Jln",
					label: "Kondisi Jalan"			
				},
				{
					fieldName: "Jns_Perker",
					label: "Jenis Perker"			
				},
				{
					fieldName: "Wilayah",
					label: "Wilayah"			
				}],
			}],
			actions: [{
				title: " Dokumentasi",
				id: "view-foto",
				className: "esri-icon-media"
			},{
				title: " Leger",
				id: "view-leger",
				className: "esri-icon-review"
			}]
		};
		
		function _viewJembatan() {
			var urlFoto = "http://www.kav-32.com/silaja/dokumentasi/Jembatan/";
			let nama_file = urlFoto + mapView.popup.selectedFeature.attributes.Id + '. Jembatan ' + mapView.popup.selectedFeature.attributes.Nama + '.xlsx';
			//alert(urlFoto + nama_file + '.xlsx');
			
			var request = new XMLHttpRequest();  
			request.open('GET', nama_file, true);
			request.onreadystatechange = function(){
				if (request.readyState === 4){
					if (request.status === 404) {  
						alert("The file does not exist!");
					} else {
						window.open(nama_file);
					}					
				}
			};
			request.send();
		}
		
		function _viewFoto() {
			var urlFoto = "http://www.kav-32.com/silaja/dokumentasi/";
			let nama_file = urlFoto + mapView.popup.selectedFeature.attributes.OID_ + '. ' + mapView.popup.selectedFeature.attributes.TOPONIM + '.xlsx';
			//alert(urlFoto + nama_file + '.xlsx');
			
			var request = new XMLHttpRequest();  
			request.open('GET', nama_file, true);
			request.onreadystatechange = function(){
				if (request.readyState === 4){
					if (request.status === 404) {  
						alert("The file does not exist!");
					} else {
						window.open(nama_file);
					}					
				}
			};
			request.send();
		}
		
		function _viewLeger() {
			var urlFoto = "http://www.kav-32.com/silaja/leger/";
			let nama_file = urlFoto + mapView.popup.selectedFeature.attributes.OID_ + '. ' + mapView.popup.selectedFeature.attributes.TOPONIM + '.xlsx';
			//alert(urlFoto + nama_file + '.xlsx');
			
			var request = new XMLHttpRequest();  
			request.open('GET', nama_file, true);
			request.onreadystatechange = function(){
				if (request.readyState === 4){
					if (request.status === 404) {  
						alert("The file does not exist!");
					} else {
						window.open(nama_file);
					}					
				}
			};
			request.send();
		}

		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/3";
		var f0 = new FeatureLayer({url,
			title: "2020",
			outFields: ["*"],
			visible: false,
			popupEnabled: true,
			labelingInfo: [trailheadsLabels]
		});
		f0.popupTemplate = template;	 	 
		
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/4";
		var f1 = new FeatureLayer({url,
			title: "2021",
			outFields: ["*"],
			visible: false,
			popupEnabled: true,
			labelingInfo: [trailheadsLabels]
		});
		f1.popupTemplate = template;	 	 
		
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/5";
		var f2 = new FeatureLayer({url,
			title: "Jalan",
			outFields: ["*"],
			visible: true,
			popupEnabled: true,
			labelingInfo: [trailheadsLabels]
		});
		f2.popupTemplate = template;	 	 
		
		var jalan = new GroupLayer({
			title: "Jalan",
			visible: true,
			layers: [f2, f1, f0],
			opacity: 0.75
		});
		
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/6";
		var jln = new FeatureLayer({url,
			title: "Kondisi Jalan",
			outFields: ["*"],
			visible: false,
			popupEnabled: true
		});
	  
		template = {
			title: "Saluran Air",
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "Kondisi",
					label: "Kondisi"
				},{
					fieldName: "Lokasi_Jln",
					label: "Lokasi Jalan"
				},{
					fieldName: "Leng",
					label: "Panjang"
				},{
					fieldName: "Klasifikas",
					label: "Klasifikasi"
				},{
					fieldName: "Wilayah",
					label: "Wilayah"
				}]
			}]
		}

		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/7";
		var air = new FeatureLayer({url,
			title: "Drainase",
			outFields: ["*"],
			visible: false,
			popupEnabled: true
		});
		air.popupTemplate = template;
	  
		template = {
			title: getTitle,
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "NAMOBJ",
					label: "Nama Objek"
				},{
					fieldName: "d_ORDE01",
					label: "Deskripsi 1"
				},{
					fieldName: "d_ORDE02",
					label: "Deskripsi 2"
				},{
					fieldName: "d_ORDE03",
					label: "Deskripsi 3"
				},{
					fieldName: "d_JNSRSR",
					label: "Jenis RSR"
				},{
					fieldName: "d_STSJRN",
					label: "Stasiun Jaringan"
				}]
			}]
		}

		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/9";
		var titik_energi = new FeatureLayer({url,
			title: "Titik Energi",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});			
		titik_energi.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/10";
		var jalur_energi = new FeatureLayer({url,
			title: "Jalur Energi",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});			
		jalur_energi.popupTemplate = template;
	  	
		var energi = new GroupLayer({
			title: "Jaringan Energi",
			visible: false,
			layers: [jalur_energi, titik_energi],
			opacity: 0.75
		});
	  	
		template = {
			title: getTitle,
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "Pemohon",
					label: "Pemohon"
				},{
					fieldName: "Lokasi",
					label: "Lokasi"
				},{
					fieldName: "Kelurahan",
					label: "Kelurahan"
				},{
					fieldName: "Kecamatan",
					label: "Kecamatan"
				},{
					fieldName: "Panjang_m",
					label: "Panjang (m)"
				},{
					fieldName: "Tgl_Rekom",
					label: "Tgl Rekomendasi"
				},{
					fieldName: "No_Rekom",
					label: "No Rekomendasi"
				}]
			}]
		}

		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/12";
		var trans = new FeatureLayer({url,
			title: "PT TransIndo",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});		
		trans.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/13";
		var mnc = new FeatureLayer({url,
			title: "PT MNCKabel",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});			
		mnc.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/14";
		var indosat = new FeatureLayer({url,
			title: "PT Indosat",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});
		indosat.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/15";
		var cemerlang = new FeatureLayer({url,
			title: "PT Cemerlang",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});		
		cemerlang.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/16";
		var bali = new FeatureLayer({url,
			title: "PT Bali",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});		
		bali.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/17";
		var sokka = new FeatureLayer({url,
			title: "PT Sokka",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});	
		sokka.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/18";
		var mora = new FeatureLayer({url,
			title: "PT Mora",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});	
		mora.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/19";
		var mega1 = new FeatureLayer({url,
			title: "Mega Akses 1",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});	
		mega1.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/20";
		var mega2 = new FeatureLayer({url,
			title: "Mega Akses 2",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});	
		mega2.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/21";
		var linknet = new FeatureLayer({url,
			title: "Link Net",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});	
		linknet.popupTemplate = template;
	  	
		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/22";
		var cendekia = new FeatureLayer({url,
			title: "Cendekia",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});		
		cendekia.popupTemplate = template;
	  	
		var fo = new GroupLayer({
			title: "Jaringan FO",
			visible: false,
			layers: [cendekia, linknet, mega2, mega1, mora, sokka, bali, cemerlang, indosat, mnc, trans],
			opacity: 0.75
		});
	  	
		template = {
			title: getTitle,
			content: [{
				type: "fields",
				fieldInfos: [{
					fieldName: "DESA",
					label: "Desa"
				},{
					fieldName: "KECAMATAN",
					label: "Kecamatan"
				},{
					fieldName: "KABUPATEN",
					label: "Kab/Kota"
				},{
					fieldName: "PROVINSI",
					label: "Provinsi"
				},{
					fieldName: "Luas__Ha_",
					label: "Luas (Ha)"
				},{
					fieldName: "PEND_LK",
					label: "Penduduk Laki2"
				},{
					fieldName: "PEND_PR",
					label: "Penduduk Perempuan"
				},{
					fieldName: "SUMBER",
					label: "Sumber"
				}]
			}]
		}

		url = "http://www.kav-32.com/arcgis/rest/services/silaja/MapServer/23";
		var bts = new FeatureLayer({url,
			title: "Batas Administrasi",
			outFields: ["*"],
			visible: true,
			popupEnabled: true
		});
		bts.popupTemplate = template;
	  
		var map = new Map({
			basemap: "topo",
			layers: [bts, fo, energi, air, jln, f2, jbt]
		});
	        
		var mapView = new MapView({
			container: "mapViewDiv",
			map: map,
			center: [107.543123, -6.885123],
			zoom: 15,
			padding: {
				top: 50,
				bottom: 0
			},
			ui: {components: []}
		});

		mapView.popup.on("trigger-action", function(event) {
			if (event.action.id === "view-foto") {
				_viewFoto();				
			}
			else if (event.action.id === "view-leger") {
				_viewLeger();				
			}
			else if (event.action.id === "view-Jembatan") {
				_viewJembatan();				
			}
		});

		mapView.when(function(){
			CalciteMapArcGISSupport.setPopupPanelSync(mapView);
		});

		var searchWidget = new Search({
			container: "searchWidgetDiv",
			view: mapView
		});
		CalciteMapArcGISSupport.setSearchExpandEvents(searchWidget);

		var home = new Home({
			view: mapView
		});
		mapView.ui.add(home, "top-left");

		var zoom = new Zoom({
			view: mapView
		});
		mapView.ui.add(zoom, "top-left");

		/*var compass = new Compass({
			view: mapView
		});
		mapView.ui.add(compass, "top-left");*/
		
		var locateBtn = new Locate({
          view: mapView
        });
        mapView.ui.add(locateBtn, "top-left");
      
		var basemapToggle = new BasemapToggle({
			view: mapView,
			secondBasemap: "satellite"
		});
		mapView.ui.add(basemapToggle, "bottom-right");          
      
		var scaleBar = new ScaleBar({
			view: mapView
		});
		mapView.ui.add(scaleBar, "bottom-left");

		var attribution = new Attribution({
			view: mapView
		});
		mapView.ui.add(attribution, "manual");

      // Panel widgets - add legend
		var legendWidget = new Legend({
			container: "legendDiv",
			view: mapView
		});
	  
		watchUtils.when(legendWidget, "container", function() {
            aspect.after(legendWidget, "scheduleRender", function(response) {
              if (query('.esri-legend__layer-caption')[0]) {
                query('.esri-legend__layer-caption')[0].style.display = 'none';
              }
            });
        });
	  
		var layerList = new LayerList({
			container: "layerDiv",
			view: mapView,
			listItemCreatedFunction: defineActions
		});
	  
		var editorWidget = new Editor({
			container: "editorDiv",
			view: mapView
		});
	  
		function defineActions(event) {

		  var item = event.item;

		  if (item.title === "2022" || item.title === "Batas Administrasi") {
			item.actionsSections = [
			  [
				{
				  title: "Go to full extent",
				  className: "esri-icon-zoom-out-fixed",
				  id: "full-extent"
				},
				{
				  title: "Layer information",
				  className: "esri-icon-description",
				  id: "information"
				}
			  ],
			  [
				{
				  title: "Increase opacity",
				  className: "esri-icon-up",
				  id: "increase-opacity"
				},
				{
				  title: "Decrease opacity",
				  className: "esri-icon-down",
				  id: "decrease-opacity"
				}
			  ]
			];
		  }
		}
	  
		layerList.on("trigger-action", function(event) {
		  
		  var visibleLayer = f2;
		  if (event.item.title === "Batas Administrasi")
			  visibleLayer = bts;		  
		  
		  var id = event.action.id;		  

		  if (id === "full-extent") {
			mapView.goTo(visibleLayer.fullExtent);
		  } else if (id === "information") {
			window.open(visibleLayer.url);
		  } else if (id === "increase-opacity") {
			
			if (visibleLayer.opacity < 1) {
			  visibleLayer.opacity += 0.25;
			}
		  } else if (id === "decrease-opacity") {
			
			if (visibleLayer.opacity > 0) {
			  visibleLayer.opacity -= 0.25;
			}
		  }
		});
		
		mapView.when(function() {
          var print = new Print({
            view: mapView,
			container: "printDiv",
            printServiceUrl:
              "http://utility.arcgisonline.com/arcgis/rest/services/Utilities/PrintingTools/GPServer/Export%20Web%20Map%20Task"
          });
        });

		var basemapGallery = new BasemapGallery({
			view: mapView,
			container: "basemapDiv"
		});

		function capitalize(string) {
			return string.charAt(0).toUpperCase() + string.slice(1);
		}
	          
        function doFind() {
			
			var value = document.getElementById("textSearch").value;
			  
			var query = new Query({
				returnGeometry: true,
				outFields: ["*"]
			});
				
			query.where = "TOPONIM LIKE '%" + value + "%' or TOPONIM LIKE '%" + value.toUpperCase() + "%' or TOPONIM LIKE '%" + value.toLowerCase() + "%' or TOPONIM LIKE '%" + capitalize(value) + "%'";

			f2.queryFeatures(query).then(showResults).catch(rejectedPromise);
        }

        var resultsTable = document.getElementById("tbl");

		resultsTable.addEventListener('click', function(event) {
			mapView.popup.close();
			const rowIndex = event.target.parentNode.rowIndex;
			
			var rowSelected = resultsTable.getElementsByTagName('tr')[rowIndex];
			var id = rowSelected.cells[0].innerHTML;

			const query = {
				objectIds: [parseInt(id)],
				outFields: ["*"],
				returnGeometry: true
			};

          f2.queryFeatures(query).then(function(results) {
				
              const graphics = results.features;
              mapView.graphics.removeAll();

              const selectedGraphic = new Graphic({
                geometry: graphics[0].geometry,
                symbol: {
                  type: "simple-line",
                  style: "solid",
                  color: "orange",
                  width: "2px", // pixels
                  outline: {
                    color: [255, 255, 0],
                    width: "2px" // points
                  }
                }
              });

              mapView.graphics.add(selectedGraphic);
			  mapView.popup.open({
					features: graphics,
					featureMenuOpen: true,
					updateLocationEnabled: true
				});
            }).catch(errorCallback);
		});

        function showResults(response) {

		  var results = response.features;

          resultsTable.innerHTML = "";

          if (results.length === 0) {
            document.getElementById("printResults").innerHTML = "<i>No results found.</i>";
            return;
          }

          var topRow = resultsTable.insertRow(0);
          var cell1 = topRow.insertCell(0);
          var cell2 = topRow.insertCell(1);
          cell1.innerHTML = "<b>OBJECT ID</b>";
          cell2.innerHTML = "<b>Nama Jalan</b>";

          document.getElementById("printResults").innerHTML = results.length + " results found!";
          results.forEach(function(findResult, i) {
			var oid = findResult.attributes.FID;
            var city = findResult.attributes.TOPONIM;

            var row = resultsTable.insertRow(i + 1);
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            cell1.innerHTML = oid;
            cell2.innerHTML = city;
          });
        }

        function rejectedPromise(error) {
          console.error("Promise didn't resolve: ", error.message);
        }
		
		function errorCallback(error) {
          console.log("error:", error);
        }

		function doClear() {
			resultsTable.innerHTML = "";
			document.getElementById("printResults").innerHTML = "";
			document.getElementById("textSearch").value = "";
			mapView.graphics.removeAll();
			mapView.popup.close();
		}

        // Run doFind() when button is clicked
        document.getElementById("findBtn").addEventListener("click", doFind);
		document.getElementById("clearBtn").addEventListener("click", doClear);
	  
    });
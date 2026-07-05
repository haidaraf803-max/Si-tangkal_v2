require([
      // ArcGIS
	  "esri/Map",
    "esri/WebMap",
    "esri/views/MapView",
    "esri/Basemap",
	  "esri/layers/FeatureLayer",
	  "esri/tasks/FindTask",	  
	  "esri/tasks/support/Query",
    "esri/tasks/support/FindParameters",
	  "esri/core/watchUtils",
	  "esri/layers/GraphicsLayer",
	  "esri/Graphic",
	  "esri/layers/GroupLayer",
	  "esri/layers/MapImageLayer",
	  "esri/layers/WMSLayer",
	  "esri/layers/GeoJSONLayer",

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
    ], function(Map, WebMap, MapView, Basemap, FeatureLayer, FindTask, Query, FindParameters, watchUtils, GraphicsLayer, Graphic, GroupLayer, MapImageLayer, WMSLayer, GeoJSONLayer, Home, Zoom, Compass, Search, Legend, BasemapToggle, BasemapGallery, ScaleBar, Attribution, LayerList, Print, Editor, Locate, Collapse, Dropdown, CalciteMaps, CalciteMapArcGISSupport, query, aspect) {

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

		var imgIco = "http://localhost:8888/sitangkal/"
		// Symbol for kesehatan Baik
		const fwySym = {
			type: "picture-marker",  
			url: imgIco + "assets/img/sehat.png",
			width: "15px",
			height: "15px"
		};

		// Symbol for Kesehatan Sedang
		const hwySym = {
			type: "picture-marker",  
			url: imgIco + "assets/img/kurang.png",
			width: "15px",
			height: "15px"
		};

		// Symbol for Kesehatan Buruk
		const stateSym = {
			type: "picture-marker",  
			url: imgIco + "assets/img/sakit.png",
			width: "15px",
			height: "15px"
		};

		// Symbol for kesehatan tidak diisi
		const otherSym = {
			type: "picture-marker",  
			url: imgIco + "assets/img/lainnya.png",
			width: "15px",
			height: "15px"
		};
		
		const phn_render = {
			type: "unique-value", // autocasts as new UniqueValueRenderer()
			legendOptions: {
			  title: "Kesehatan Pohon",
			},
			defaultSymbol: otherSym,
			defaultLabel: "Lainnya",
			field: "kesehatan",
	
			uniqueValueInfos: [
			  {
				value: "Sehat", // code for interstates/freeways
				symbol: fwySym,
				label: "Sehat",
			  },
			  {
				value: "Kurang Sehat", // code for U.S. highways
				symbol: hwySym,
				label: "Sedang",
			  },
			  {
				value: "Sakit", // code for U.S. highways
				symbol: stateSym,
				label: "Sakit",
			  },
			],
		};
		  
		var imgUrl = "http://localhost:8888/sitangkal/assets/foto/";
	  
		var template = {
			title: "Tangkal Pohon",
			content: [{
				type: "media",
				mediaInfos: [{
					//title: 'Foto',
					//caption: '{foto}',
					type: 'image',
					value: {
						sourceURL: imgUrl + "{foto}",
						linkURL: imgUrl + "{foto}"
					}
				}]
			}, {
				type: "fields",
				fieldInfos: [{
					fieldName: "No_Pohon",
					label: "No Pohon"
				},{
					fieldName: "Nama_Lokal",
					label: "Nama Lokal"
				},{
					fieldName: "Nama_Latin",
					label: "Nama Latin"
				},{
					fieldName: "Family",
					label: "Family"
				},{
					fieldName: "Tahun_Tana",
					label: "Tahun Tanam"
				},{
					fieldName: "Habitus",
					label: "Habitus"
				},{
					fieldName: "Status_Kel",
					label: "Status"
				},{
					fieldName: "Volume",
					label: "Volume"
				},{
					fieldName: "Kelas_Awet",
					label: "Kelas Awet"
				},{
					fieldName: "Kelas_Kuat",
					label: "Kelas Kuat"
				},{
					fieldName: "Berat_Jeni",
					label: "Berat Jenis"
				},{
					fieldName: "Kesehatan",
					label: "Kesehatan"
				},{
					fieldName: "Serapan_CO",
					label: "Serapan CO2"
				},{
					fieldName: "Produksi_O",
					label: "Produksi Oksigen"
				},{
					fieldName: "latitude",
					label: "X"
				},{
					fieldName: "longitude",
					label: "Y"
				},{
					fieldName: "Nama_Jalan",
					label: "Nama Jalan"
				},{
					fieldName: "Kecamatan",
					label: "Kecamatan"
				},{
					fieldName: "Kelurahan",
					label: "Kelurahan"
				}]
			}]
		}


		var pohon_ = new GeoJSONLayer({
			url: "http://localhost:8888/sitangkal/api.php",
			title: "Tangkal Pohon",
			renderer: phn_render,
			popupTemplate: template
		});

		var urlGeo = "https://c-map.cimahikota.go.id/geoserver/cimahi/wms";
		var p_kahati = new WMSLayer({
			url: urlGeo,
			title: "Pohon Kahati",
			listMode: "hide-children",
			sublayers: [{
				name: "cimahi:SITANGKAL POHON KAHATI",
				outFields: ["*"],
				legendEnabled: true,
				popupEnabled: false,
				queryable: true,
				//popupTemplate: template,
				legendUrl: 'https://c-map.cimahikota.go.id/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=cimahi:SITANGKAL POHON KAHATI'
			}]
		});

		var p_rw = new WMSLayer({
			url: urlGeo,
			title: "Pohon RW",
			listMode: "hide-children",
			sublayers: [{
				name: "cimahi:SITANGKAL POHON RW",
				outFields: ["*"],
				legendEnabled: true,
				popupEnabled: false,
				queryable: true,
				//popupTemplate: template,
				legendUrl: 'https://c-map.cimahikota.go.id/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=cimahi:SITANGKAL POHON RW'
			}]
		});

		/*var p_pohon = new WMSLayer({
			url: urlGeo,
			title: "Pohon",
			listMode: "hide-children",
			sublayers: [{
				name: "cimahi:SITANGKAL POHON",
				outFields: ["*"],
				legendEnabled: true,
				popupEnabled: true,
				queryable: true,
				popupTemplate: tem_pohon,
				legendUrl: 'https://c-map.cimahikota.go.id/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=cimahi:SITANGKAL POHON'
			}]
		});*/

		var p_rth = new WMSLayer({
			url: urlGeo,
			title: "RTH",
			listMode: "hide-children",
			sublayers: [{
				name: "cimahi:SITANGKAL RTH",
				outFields: ["*"],
				legendEnabled: true,
				popupEnabled: false,
				queryable: true,
				//popupTemplate: template,
				legendUrl: 'https://c-map.cimahikota.go.id/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=cimahi:SITANGKAL RTH'
			}]
		});

		var bts = new WMSLayer({
			url: urlGeo,
			title: "Batas Administrasi",
			listMode: "hide-children",
			sublayers: [{
				name: "cimahi:ADMINISTRASI_AR",
				outFields: ["*"],
				legendEnabled: true,
				popupEnabled: false,
				queryable: true,
				//popupTemplate: template,
				legendUrl: 'https://c-map.cimahikota.go.id/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=cimahi:ADMINISTRASI_AR'
			}]
		});	

		var fu = new WMSLayer({
			url: urlGeo,//"https://datangahiji.cimahikota.go.id/geoserver/cmdbuild/wms",
			title: "Foto Udara",
			listMode: "hide-children",
			visible: false,
			sublayers: [{
				name: "cimahi:fotoudara"
			}]
		})

		var myBasemap = new Basemap({
		  baseLayers: [fu],
		  title: "Custom Basemap",
		  id: "myBasemap"
		});
		//basemaps.push(myBasemap);
	  
		var map = new Map({
			basemap: "streets",
			//layers: [fu, bts, rth, phn, rw, kahati]
			layers: [fu, bts, p_rth, pohon_, p_rw, p_kahati]
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
			view: mapView,
			style: "ruler",
			unit: "metric"
		});
		mapView.ui.add(scaleBar, "bottom-left");

		var attribution = new Attribution({
			view: mapView,
			visible: false
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
			selectionEnabled: true,
			listItemCreatedFunction: defineActions
		});

		const pointInfos = {
			layer: [baros, cibabat, cibeber],
			formTemplate: { // autocasts to FormTemplate
			  elements: [{ // autocasts to Field Elements
				type: "field",
				fieldName: "No_Pohon",
				label: "No Pohon"
			  }, {
				type: "field",
				fieldName: "Nama_Lokal",
				label: "Nama Lokal"
			  }, {
				type: "field",
				fieldName: "Nama_Latin",
				label: "Nama Latin"
			  }, {
				type: "field",
				fieldName: "Family",
				label: "Family"
			  }, {
				type: "field",
				fieldName: "Tahun_Tana",
				label: "Tahun Tanam"
			  }]
			}
		};
	  
		var editorWidget = new Editor({
			container: "editorDiv",
			view: mapView,
			layerInfos: [pointInfos]
		});
	  
		function defineActions(event) {

		  var item = event.item;

		  if (item.layer.type != "group") {
			item.panel = {
			  content: "legend",
			  open: false
			};
		  }

		  if (item.title === "Tangkal Pohon" || item.title === "Cibabat" || item.title === "Cibeber" || item.title === "Citeureup" || item.title === "Karang Mekar" || item.title === "Leuwigajah" || item.title === "Padasuka" || item.title === "Pasirkaliki" || item.title === "Setiamanah" || item.title === "Utama" || item.title === "Pohon Kahati" || item.title === "Pohon RW" || item.title === "RTH" || item.title === "Batas Administrasi") {
			item.actionsSections = [
			  [
				{
				  title: "Go to full extent",
				  className: "esri-icon-zoom-out-fixed",
				  id: "full-extent"
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
		  
		  	var visibleLayer = pohon_;
		  	if (event.item.title === "Cibabat")
			  visibleLayer = cibabat;		  
		  	else if (event.item.title === "Cibeber")
			  visibleLayer = cibeber;		  
			else if (event.item.title === 'Citeureup')
			  visibleLayer = citeureup;
		  	else if (event.item.title === 'Karang Mekar')
			  visibleLayer = karang_mekar;
		  	else if (event.item.title === 'Leuwigajah')
			  visibleLayer = leuwigajah;
		  	else if (event.item.title === 'Padasuka')
			  visibleLayer = padasuka;
		  	else if (event.item.titleyr === 'Pasirkaliki')
			  visibleLayer = pasirkaliki;
		  	else if (event.item.title === 'Setiamanah')
			  visibleLayer = setiamanah;
		  	else if (event.item.title === 'Utama')
			  visibleLayer = utama;
			else if (event.item.title === 'RTH')
			  visibleLayer = rth;
			else if (event.item.title === 'Pohon RW')
			  visibleLayer = rw;
			else if (event.item.title === 'Pohon Kahati')
			  visibleLayer = kahati;
			else if (event.item.title === "Batas Administrasi")
			  visibleLayer = bts;		  
		  
		  var id = event.action.id;		  

		  if (id === "full-extent") {
			mapView.goTo(visibleLayer.fullExtent);
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
				
			query.where = "Nama_Jalan LIKE '%" + value + "%' or Nama_Jalan LIKE '%" + value.toUpperCase() + "%' or Nama_Jalan LIKE '%" + value.toLowerCase() + "%' or Nama_Jalan LIKE '%" + capitalize(value) + "%'";

			pohon.queryFeatures(query).then(showResults).catch(rejectedPromise);
        }

        var resultsTable = document.getElementById("tbl");

		resultsTable.addEventListener('click', function(event) {
			mapView.popup.close();
			const rowIndex = event.target.parentNode.rowIndex;
			
			var rowSelected = resultsTable.getElementsByTagName('tr')[rowIndex];
			var id = rowSelected.cells[0].innerHTML;
			var lyr = rowSelected.cells[2].innerHTML;
			var layer;
			if (lyr === 'Baros')
				layer = baros;
			else if (lyr === 'Cibabat')
				layer = cibabat;
			else if (lyr === 'Cibeber')
				layer = cibeber;
			else if (lyr === 'Citeureup')
				layer = citeureup;
			else if (lyr === 'Karang Mekar')
				layer = karang_mekar;
			else if (lyr === 'Leuwigajah')
				layer = leuwigajah;
			else if (lyr === 'Padasuka')
				layer = padasuka;
			else if (lyr === 'Pasirkaliki')
				layer = pasirkaliki;
			else if (lyr === 'Setiamanah')
				layer = setiamanah;
			else if (lyr === 'Utama')
				layer = utama;

			const query = {
				objectIds: [parseInt(id)],
				outFields: ["*"],
				returnGeometry: true
			};

          layer.queryFeatures(query).then(function(results) {
				
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
		  var cell3 = topRow.insertCell(2);
          cell1.innerHTML = "<b>ID</b>";
          cell2.innerHTML = "<b>Nama Jalan</b>";
		  cell3.innerHTML = "<b>Kelurahan</b>";

          document.getElementById("printResults").innerHTML = results.length + " results found!";
          results.forEach(function(findResult, i) {
			var oid = findResult.attributes.OBJECTID;
            var city = findResult.attributes.Nama_Jalan;
			var region = findResult.attributes.Kelurahan;

            var row = resultsTable.insertRow(i + 1);
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
			var cell3 = row.insertCell(2);
            cell1.innerHTML = oid;
            cell2.innerHTML = city;
			cell3.innerHTML = region;
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
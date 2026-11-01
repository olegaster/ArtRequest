/**
 * Art Request System for MODX Revolution
 * Copyright © 2014, 2018 Aleksandr Romanov <x0rhamster@ya.ru>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

(function () {
	"use strict";
	
	Ext.ns("YAHOO.widget.SWF");
	
	YAHOO.widget.SWF.eventHandler = Ext.FlashEventProxy.onEvent;
	
	Ext.ns("ArtRequest");
	
	ArtRequest.bind = function (fn, thisArg /*, bindArgs */) {
		var bindArgs = [].slice.call(arguments, 2);
		return function () {
			return fn.apply(thisArg, bindArgs.concat([].slice.call(arguments)));
		};
	};
	
	ArtRequest.merge = function () {
		var empty = {};
		
		var result = {};
		for (var i = 0; i < arguments.length; ++i) {
			for (var x in arguments[i]) {
				if (arguments[i][x] != empty[x]) {
					result[x] = arguments[i][x];
				}
			}
		}
		
		return result;
	};
	
	ArtRequest.assets_url = "";
	ArtRequest.connector_url = "";
	ArtRequest.uploads_url = "";
	
	Ext.onReady(function () {
		var elements = Ext.query("[data-artrequest-render]");
		for (var i = 0; i < elements.length; ++i) {
			MODx.load({
				"xtype": "modx-component",
				"components": [{
					"xtype": elements[i].getAttribute("data-artrequest-render"),
					"renderTo": elements[i]
				}]
			});
		}
		
		if (elements.length) {
			Ext.getCmp("modx-layout").hideLeftbar(false, true);
		}
	});
	
	// ---------------------------------------------------------------------------------------------
	
	Ext.reg("artrequest-composite", ArtRequest.Composite = Ext.extend(Ext.form.CompositeField, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"width": 220
			});
			
			ArtRequest.Composite.superclass.constructor.call(this, config);
			
			this.on("disable", function () {
				this.itemCt && this.itemCt.child("label").addClass("x-item-disabled");
			});
			
			this.on("enable", function () {
				this.itemCt && this.itemCt.child("label").removeClass("x-item-disabled");
			});
			
			this.on("afterrender", function () {
				if (this.disabled) {
					this.fireEvent("disable", this);
				}
			});
		}
	}));
	
	Ext.reg("artrequest-trigger", ArtRequest.Trigger = Ext.extend(Ext.form.TriggerField, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"width": 220
			});
			
			ArtRequest.Trigger.superclass.constructor.call(this, config);
			
			this.on("disable", function () {
				this.itemCt && this.itemCt.child("label").addClass("x-item-disabled");
			});
			
			this.on("enable", function () {
				this.itemCt && this.itemCt.child("label").removeClass("x-item-disabled");
			});
			
			this.on("afterrender", function () {
				if (this.disabled) {
					this.fireEvent("disable", this);
				}
			});
		}
	}));
	
	Ext.reg("artrequest-combo", ArtRequest.Combo = Ext.extend(MODx.combo.ComboBox, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"url": ArtRequest.connector_url,
				"emptyText": _("notset"),
				"width": 220
			});
			
			ArtRequest.Combo.superclass.constructor.call(this, config);
			
			this.config.baseParams = ArtRequest.merge(this.config.baseParams);
			
			this.on("disable", function () {
				this.itemCt && this.itemCt.child("label").addClass("x-item-disabled");
			});
			
			this.on("enable", function () {
				this.itemCt && this.itemCt.child("label").removeClass("x-item-disabled");
			});
			
			this.on("afterrender", function () {
				if (this.disabled) {
					this.fireEvent("disable", this);
				}
			});
		},
		
		"filter": function (baseParams) {
			Ext.apply(this.store.baseParams, baseParams || {});
			this.lastQuery = null;
			ArtRequest.Combo.superclass.reset.call(this);
		},
		
		"reset": function () {
			this.store.baseParams = ArtRequest.merge(this.config.baseParams);
			this.lastQuery = null;
			ArtRequest.Combo.superclass.reset.call(this);
		}
	}));
	
	Ext.reg("artrequest-grid", ArtRequest.Grid = Ext.extend(MODx.grid.Grid, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"url": ArtRequest.connector_url,
				"remoteSort": true,
				"paging": true
			});
			
			ArtRequest.Grid.superclass.constructor.call(this, config);
			
			this.config.baseParams = ArtRequest.merge(this.config.baseParams);
		},
		
		"filter": function (baseParams) {
			Ext.apply(this.store.baseParams, baseParams || {});
			this.bottomToolbar.changePage(1);
			this.refresh();
		},
		
		"reset": function () {
			this.store.baseParams = ArtRequest.merge(this.config.baseParams);
			this.bottomToolbar.changePage(1);
			this.refresh();
		},
		
		"rendDateTime": function (value) {
			return new Date(value).format(MODx.config.manager_date_format + " " + MODx.config.manager_time_format);
		},
		
		"rendList": function (value) {
			return value && value.join("<br>");
		},
		
		"_loadStore": function () {
			ArtRequest.Grid.superclass._loadStore.call(this);
			this.store.sortInfo = {"field": this.config.sortBy || "id", "direction": this.config.sortDir || "ASC"};
		}
	}));
	
	Ext.reg("artrequest-window", ArtRequest.Window = Ext.extend(Ext.Window, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"closeAction": "hide",
				"cls": "modx-window",
				"modal": true,
				"resizable": false,
				"shadow": true,
				"width": 1000
			});
			
			ArtRequest.Window.superclass.constructor.call(this, config);
		}
	}));
	
	Ext.reg("artrequest-recordwindow", ArtRequest.RecordWindow = Ext.extend(ArtRequest.Window, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"record": {},
				"layout": "form",
				"labelAlign": "top"
			});
			
			ArtRequest.RecordWindow.superclass.constructor.call(this, config);
		},
		
		"_refresh": function () {
			this.cascade(function (cmp) {
				(typeof this.record[cmp.name] != "undefined")
					&& cmp.setValue(this.record[cmp.name]);
			}, this);
			
			this.onRefresh();
		},
		
		"onRefresh": Ext.emptyFn
	}));
	
	Ext.reg("artrequest-localrecordwindow", ArtRequest.LocalRecordWindow = Ext.extend(ArtRequest.RecordWindow, {
		"showRecord": function (record, animateTarget, callback, scope) {
			this.record = record;
			this._refresh();
			
			this.show(animateTarget, callback, scope);
		},
		
		"refresh": function () {
			this._refresh();
		}
	}));
	
	Ext.reg("artrequest-remoterecordwindow", ArtRequest.RemoteRecordWindow = Ext.extend(ArtRequest.RecordWindow, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"url": ArtRequest.connector_url,
				"baseParams": {"action": ""},
				"pkField": "id"
			});
			
			ArtRequest.RemoteRecordWindow.superclass.constructor.call(this, config);
		},
		
		"_fetchRecord": function (pk, callbackSuccess, callbackFailure) {
			var params = ArtRequest.merge(this.baseParams);
			params[this.pkField] = pk;
			
			this.getEl() && this.getEl().mask(Ext.LoadMask.prototype.msg, Ext.LoadMask.prototype.msgCls);
			
			MODx.Ajax.request({
				"url": this.url,
				"params": params,
				"listeners": {
					"success": {"fn": function (r) {
						this.record = r.object;
						this._refresh();
						
						this.getEl() && this.getEl().unmask();
						callbackSuccess && callbackSuccess();
					}, "scope": this},
					"failure": {"fn": function (r) {
						MODx.msg.alert(_("error"), r.message || _("artrequest.error_fetch_record"));
						
						this.getEl() && this.getEl().unmask();
						callbackFailure && callbackFailure();
					}, "scope": this}
				}
			});
		},
		
		"showRecord": function (pk, animateTarget, callback, scope) {
			if (callback) {
				callback = ArtRequest.bind(callback, scope || this);
			}
			
			this._fetchRecord(pk, ArtRequest.bind(this.show, this, animateTarget, callback), callback);
		},
		
		"refresh": function () {
			this._fetchRecord(this.record[this.pkField]);
		}
	}));
	
	// ---------------------------------------------------------------------------------------------
	
	Ext.reg("artrequest-requests", ArtRequest.RequestsGrid = Ext.extend(ArtRequest.Grid, {
		"constructor": function (config) {
			var sm = new Ext.grid.CheckboxSelectionModel();
			
			config = config || {};
			Ext.applyIf(config, {
				"baseParams": {"action": "getList"},
				"fields": ["id", "createdon", "hidden", "person_names", "orgperson_names"],
				"sortBy": "createdon",
				"sortDir": "DESC",
				"sm": sm,
				"columns": [sm, {
					"header": _("id"),
					"dataIndex": "id",
					"sortable": true,
					"width": 50
				}, {
					"header": _("createdon"),
					"dataIndex": "createdon",
					"sortable": true,
					"width": 100,
					"renderer": this.rendDateTime
				}, {
					"header": _("artrequest.applicants"),
					"dataIndex": "applicant_names",
					"width": 150,
					"renderer": ArtRequest.bind(function (value, metadata, record) {
						return this.rendList(record.get("person_names").concat(record.get("orgperson_names")));
					}, this)
				}],
				"tbar": [{
					"text": _("artrequest.bulk_mail_all"),
					"handler": function () {
						var params = {"action": "mailall"};
						Ext.applyIf(params, this.store.baseParams);
						
						Ext.Msg.prompt(_("artrequest.bulk_mail_all"), _("artrequest.bulk_mail_prompt"), function (button, value) {
							if (button != "ok") {
								return;
							}
							
							if (!Ext.form.VTypes.email(value)) {
								MODx.msg.alert(_("error"), _("ext_inv_email"));
								return;
							}
							
							params["to"] = value;
							
							MODx.Ajax.request({
								"method": "POST",
								"url": ArtRequest.connector_url,
								"params": params,
								"listeners": {
									"success": {"fn": function () {
										MODx.msg.alert(_("success"), _("artrequest.bulk_mail_success"));
									}, "scope": this},
									"failure": {"fn": function () {
										MODx.msg.alert(_("error"), _("error_sending_email"));
									}, "scope": this}
								}
							});
						}, this);
					},
					"scope": this
				}, {
					"text": _("artrequest.bulk_mail"),
					"handler": function () {
						if (!this.selModel.selections.length) {
							return;
						}
						
						var params = {"action": "mail"};
						this.selModel.selections.each(function () {
							params["id[" + this.get("id") + "]"] = this.get("id");
						});
						
						Ext.Msg.prompt(_("artrequest.bulk_mail"), _("artrequest.bulk_mail_prompt"), function (button, value) {
							if (button != "ok") {
								return;
							}
							
							if (!Ext.form.VTypes.email(value)) {
								MODx.msg.alert(_("error"), _("ext_inv_email"));
								return;
							}
							
							params["to"] = value;
							
							MODx.Ajax.request({
								"method": "POST",
								"url": ArtRequest.connector_url,
								"params": params,
								"listeners": {
									"success": {"fn": function () {
										MODx.msg.alert(_("success"), _("artrequest.bulk_mail_success"));
									}, "scope": this},
									"failure": {"fn": function () {
										MODx.msg.alert(_("error"), _("error_sending_email"));
									}, "scope": this}
								}
							});
						}, this);
					},
					"scope": this
				}, {
					"text": _("artrequest.bulk_print"),
					"handler": function () {
						if (!this.selModel.selections.length) {
							return;
						}
						
						var form = Ext.DomHelper.append(document.body, {
							"tag": "form",
							"method": "POST",
							"action": "?a=request/print&namespace=artrequest",
							"target": "_blank"
						});
						
						this.selModel.selections.each(function () {
							Ext.DomHelper.append(form, {
								"tag": "input",
								"type": "hidden",
								"name": "id[]",
								"value": this.get("id")
							});
						});
						
						form.submit();
						
						while (form.firstChild) form.removeChild(form.firstChild);
						form.parentNode.removeChild(form);
					},
					"scope": this
				}, {
					"text": _("delete"),
					"handler": function () {
						if (!this.selModel.selections.length) {
							return;
						}
						
						var params = {"action": "delete"};
						this.selModel.selections.each(function () {
							params["id[" + this.get("id") + "]"] = this.get("id");
						});
						
						MODx.msg.confirm({
							"text": _("artrequest.delete_requests_confirm"),
							"url": ArtRequest.connector_url,
							"params": params,
							"listeners": {
								"success": {"fn": this.refresh, "scope": this}
							}
						});
					},
					"scope": this
				}],
				"listeners": {
					"rowdblclick": function (cmp, index, e) {
						if (!cmp.requestWindow) {
							cmp.requestWindow = MODx.load({"xtype": "artrequest-requests-requestwindow"});
						}
						
						cmp.getEl().mask(Ext.LoadMask.prototype.msg, Ext.LoadMask.prototype.msgCls);
						
						cmp.requestWindow.showRecord(
							cmp.store.data.items[index].get("id"),
							e.target,
							cmp.getEl().unmask,
							cmp.getEl()
						);
					}
				}
			});
			
			ArtRequest.RequestsGrid.superclass.constructor.call(this, config);
			
			this.viewConfig.getRowClass = function (record, rowIndex, rp, ds) {
				return record.get("hidden") ? "x-grid3-row-dimmed" : "";
			};
		}
	}));
	
	Ext.reg("artrequest-requests-filters", ArtRequest.RequestsFiltersPanel = Ext.extend(MODx.Panel, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"requests": "artrequest-requests",
				"layout": "form",
				"labelAlign": "top",
				"items": [{
					"xtype": "artrequest-combo",
					"baseParams": {"action": "federaldistrict/getList"},
					"fields": ["name"],
					"valueField": "name",
					"fieldLabel": _("artrequest.federaldistrict"),
					"listeners": {
						"select": {"fn": function (cmp) {
							var federalsubject = this.getComponent("artrequest-requestfilters-federalsubject");
							federalsubject.filter({"federaldistrict": cmp.getValue()});
							federalsubject.enable();
							
							this.requests.filter({"federaldistrict": cmp.getValue(), "federalsubject": undefined});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-combo",
					"id": "artrequest-requestfilters-federalsubject",
					"baseParams": {"action": "federalsubject/getList"},
					"fields": ["name"],
					"valueField": "name",
					"disabled": true,
					"fieldLabel": _("artrequest.federalsubject"),
					"listeners": {
						"select": {"fn": function (cmp) {
							this.requests.filter({"federalsubject": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-combo",
					"baseParams": {"action": "requestreason/getList"},
					"fieldLabel": _("artrequest.requestreason"),
					"listeners": {
						"select": {"fn": function (cmp) {
							this.requests.filter({"requestreason": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-composite",
					"fieldLabel": _("createdon"),
					"items": [{
						"xtype": "datefield",
						"format": MODx.config.manager_date_format,
						"flex": 1,
						"listeners": {
							"change": {"fn": function (cmp) {
								this.requests.filter({"createdon_from": cmp.getValue()});
							}, "scope": this}
						}
					}, {
						"xtype": "datefield",
						"format": MODx.config.manager_date_format,
						"flex": 1,
						"listeners": {
							"change": {"fn": function (cmp) {
								this.requests.filter({"createdon_to": cmp.getValue()});
							}, "scope": this}
						}
					}]
				}, {
					"xtype": "artrequest-combo",
					"mode": "local",
					"store": [
						["person", _("artrequest.applicants_person")],
						["orgperson", _("artrequest.applicants_orgperson")],
						["orgpersons", _("artrequest.applicants_orgpersons")]
					],
					"fieldLabel": _("artrequest.applicants"),
					"listeners": {
						"select": {"fn": function (cmp) {
							this.requests.filter({"applicants": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-combo",
					"mode": "local",
					"store": [
						["1", _("male")],
						["0", _("female")]
					],
					"fieldLabel": _("artrequest.person_gender"),
					"listeners": {
						"select": {"fn": function (cmp) {
							this.requests.filter({"person_ismale": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "textfield",
					"fieldLabel": _("artrequest.applicant_name"),
					"width": 220,
					"listeners": {
						"change": {"fn": function (cmp) {
							this.requests.filter({"applicant_name": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "textfield",
					"fieldLabel": _("artrequest.request_id"),
					"width": 220,
					"listeners": {
						"change": {"fn": function (cmp) {
							this.requests.filter({"id": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "button",
					"text": _("artrequest.apply_filter"),
					"isFormField": true
				}, {
					"xtype": "button",
					"text": _("clear_filter"),
					"isFormField": true,
					"hideLabel": true,
					"listeners": {
						"click": {"fn": function () {
							this.items.each(function () {
								this.reset && this.reset();
								
								var config = this.config || this.initialConfig || {};
								config && config.disabled && this.disable();
							});
							
							this.requests.reset();
						}, "scope": this}
					}
				}],
				"listeners": {
					"afterrender": {"fn": function () {
						this.requests = Ext.getCmp(this.config.requests);
					}, "scope": this}
				}
			});
			
			ArtRequest.RequestsFiltersPanel.superclass.constructor.call(this, config);
		}
	}));
	
	Ext.reg("artrequest-requests-requestwindow", ArtRequest.RequestsRequestWindow = Ext.extend(ArtRequest.RemoteRecordWindow, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"baseParams": {"action": "get"},
				"items": [{
					"layout": "column",
					"items": [{
						"width": 220,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "editorgrid",
							"name": "applicants",
							"store": new Ext.data.ArrayStore({"fields": ["name", "type", "record"]}),
							"columns": [{"id": "name", "dataIndex": "name"}],
							"autoHeight": true,
							"autoExpandColumn": "name",
							"isFormField": true,
							"fieldLabel": _("artrequest.applicants"),
							"hideHeaders": true,
							"viewConfig": {
								"deferEmptyText": false,
								"emptyText": _("ext_emptymsg")
							},
							"listeners": {
								"rowdblclick": function (cmp, index, e) {
									if (cmp.store.data.items[index].get("type") == "person") {
										if (!cmp.personWindow) {
											cmp.personWindow = MODx.load({"xtype": "artrequest-requests-requestpersonwindow"});
										}
										
										cmp.personWindow.showRecord(
											cmp.store.data.items[index].get("record"),
											e.target
										);
									} else if (cmp.store.data.items[index].get("type") == "orgperson") {
										if (!cmp.orgPersonWindow) {
											cmp.orgPersonWindow = MODx.load({"xtype": "artrequest-requests-requestorgpersonwindow"});
										}
										
										cmp.orgPersonWindow.showRecord(
											cmp.store.data.items[index].get("record"),
											e.target
										);
									}
								}
							}
						}]
					}, {
						"columnWidth": 1,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "textarea",
							"readOnly": true,
							"name": "contacts",
							"anchor": "100%",
							"height": 100,
							"fieldLabel": _("artrequest.request_contacts")
						}]
					}]
				}]
			});
			
			ArtRequest.RequestsRequestWindow.superclass.constructor.call(this, config);
		},
		
		"onRefresh": function () {
			this.setTitle(_("artrequest.request_title", this.record));
			
			var applicants = [];
			for (var i = 0; i < this.record.orgpersons.length; ++i) {
				applicants.push([this.record.orgpersons[i].name, "orgperson", this.record.orgpersons[i]]);
			}
			for (var i = 0; i < this.record.persons.length; ++i) {
				applicants.push([this.record.persons[i].name, "person", this.record.persons[i]]);
			}
			
			this.find("name", "applicants")[0].store.loadData(applicants);
		}
	}));
	
	Ext.reg("artrequest-requests-requestpersonwindow", ArtRequest.RequestsRequestPersonWindow = Ext.extend(ArtRequest.LocalRecordWindow, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"width": 800,
				"items": [{
					"layout": "column",
					"items": [{
						"columnWidth": 1,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"layout": "column",
							"items": [{
								"columnWidth": 1,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "name",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_name")
								}]
							}, {
								"width": 80,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "gender",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_gender")
								}]
							}, {
								"width": 130,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "birthdate",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_birthdate")
								}]
							}]
						}, {
							"height": 15
						}, {
							"layout": "column",
							"items": [{
								"columnWidth": .5,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "federaldistrict",
									"anchor": "100%",
									"fieldLabel": _("artrequest.federaldistrict")
								}]
							}, {
								"columnWidth": .5,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "federalsubject",
									"anchor": "100%",
									"fieldLabel": _("artrequest.federalsubject")
								}]
							}]
						}, {
							"height": 15
						}, {
							"layout": "column",
							"items": [{
								"width": 150,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "postal",
									"anchor": "100%",
									"fieldLabel": _("artrequest.postal")
								}]
							}, {
								"columnWidth": 1,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "city",
									"anchor": "100%",
									"fieldLabel": _("artrequest.city")
								}]
							}]
						}, {
							"xtype": "textfield",
							"readOnly": true,
							"name": "address",
							"anchor": "100%",
							"fieldLabel": _("artrequest.address")
						}, {
							"xtype": "textfield",
							"readOnly": true,
							"name": "reason",
							"anchor": "100%",
							"fieldLabel": _("artrequest.requestreason")
						}]
					}, {
						"width": 250,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "box",
							"name": "photo",
							"autoEl": {
								"tag": "img",
								"alt": "",
								"style": {
									"max-width": "250px",
									"max-height": "260px"
								}
							},
							"isFormField": true,
							"fieldLabel": _("artrequest.applicant_photo"),
							"getValue": function () {
								return this.getEl() ? this.getEl().getAttribute("src") : this.autoEl.src;
							},
							"setValue": function (src) {
								src = ArtRequest.uploads_url + src;
								
								if (this.getEl()) {
									this.getEl().set({"src": ""});
									this.getEl().set({"src": src});
								} else {
									this.autoEl.src = "";
									this.autoEl.src = src;
								}
							}
						}, {
							"xtype": "button",
							"text": _("artrequest.open_blank"),
							"isFormField": true,
							"hideLabel": true,
							"listeners": {
								"click": {"fn": function () {
									open(this.find("name", "photo")[0].getValue());
								}, "scope": this}
							}
						}]
					}]
				}, {
					"height": 15
				}, {
					"layout": "column",
					"items": [{
						"columnWidth": .5,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "textarea",
							"readOnly": true,
							"name": "awards",
							"anchor": "100%",
							"fieldLabel": _("artrequest.applicant_awards")
						}]
					}, {
						"columnWidth": .5,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "textarea",
							"readOnly": true,
							"name": "contacts",
							"anchor": "100%",
							"fieldLabel": _("artrequest.applicant_contacts")
						}]
					}]
				}, {
					"layout": "column",
					"bodyStyle": "padding-top: 15px",
					"defaults": {
						"columnWidth": 1 / 3
					},
					"items": [{
						"xtype": "button",
						"itemId": "work1",
						"text": _("artrequest.work") + " 1",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work1,
										"name": this.record.work1name,
										"about": this.record.work1about,
										"framing": this.record.work1framing
									}, this);
							}, "scope": this}
						}
					}, {
						"xtype": "button",
						"itemId": "work2",
						"text": _("artrequest.work") + " 2",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work2,
										"name": this.record.work2name,
										"about": this.record.work2about,
										"framing": this.record.work2framing
									}, this);
							}, "scope": this}
						}
					}, {
						"xtype": "button",
						"itemId": "work3",
						"text": _("artrequest.work") + " 3",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work3,
										"name": this.record.work3name,
										"about": this.record.work3about,
										"framing": this.record.work3framing
									}, this);
							}, "scope": this}
						}
					}]
				}]
			});
			
			ArtRequest.RequestsRequestPersonWindow.superclass.constructor.call(this, config);
		},
		
		"onRefresh": function () {
			this.setTitle(this.record.name);
			
			var toggle = { "true": "show", "false": "hide" };
			this.find("itemId", "work1")[0][toggle[!!this.record.work1]]();
			this.find("itemId", "work2")[0][toggle[!!this.record.work2]]();
			this.find("itemId", "work3")[0][toggle[!!this.record.work3]]();
		}
	}));
	
	Ext.reg("artrequest-requests-requestorgpersonwindow", ArtRequest.RequestsRequestOrgPersonWindow = Ext.extend(ArtRequest.LocalRecordWindow, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"width": 800,
				"items": [{
					"layout": "column",
					"items": [{
						"columnWidth": 1,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"layout": "column",
							"items": [{
								"columnWidth": 1,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "name",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_name")
								}]
							}, {
								"width": 80,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "gender",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_gender")
								}]
							}, {
								"width": 130,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "birthdate",
									"anchor": "100%",
									"fieldLabel": _("artrequest.person_birthdate")
								}]
							}]
						}, {
							"height": 15
						}, {
							"layout": "column",
							"items": [{
								"columnWidth": .5,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "federaldistrict",
									"anchor": "100%",
									"fieldLabel": _("artrequest.federaldistrict")
								}]
							}, {
								"columnWidth": .5,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "federalsubject",
									"anchor": "100%",
									"fieldLabel": _("artrequest.federalsubject")
								}]
							}]
						}, {
							"height": 15
						}, {
							"layout": "column",
							"items": [{
								"width": 150,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "postal",
									"anchor": "100%",
									"fieldLabel": _("artrequest.postal")
								}]
							}, {
								"columnWidth": 1,
								"layout": "form",
								"labelAlign": "top",
								"items": [{
									"xtype": "textfield",
									"readOnly": true,
									"name": "city",
									"anchor": "100%",
									"fieldLabel": _("artrequest.city")
								}]
							}]
						}, {
							"xtype": "textfield",
							"readOnly": true,
							"name": "address",
							"anchor": "100%",
							"fieldLabel": _("artrequest.address")
						}, {
							"xtype": "textfield",
							"readOnly": true,
							"name": "reason",
							"anchor": "100%",
							"fieldLabel": _("artrequest.requestreason")
						}]
					}, {
						"width": 250,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "box",
							"name": "photo",
							"autoEl": {
								"tag": "img",
								"alt": "",
								"style": {
									"max-width": "250px",
									"max-height": "260px"
								}
							},
							"isFormField": true,
							"fieldLabel": _("artrequest.applicant_photo"),
							"getValue": function () {
								return this.getEl() ? this.getEl().getAttribute("src") : this.autoEl.src;
							},
							"setValue": function (src) {
								src = ArtRequest.uploads_url + src;
								
								if (this.getEl()) {
									this.getEl().set({"src": ""});
									this.getEl().set({"src": src});
								} else {
									this.autoEl.src = "";
									this.autoEl.src = src;
								}
							}
						}, {
							"xtype": "button",
							"text": _("artrequest.open_blank"),
							"isFormField": true,
							"hideLabel": true,
							"listeners": {
								"click": {"fn": function () {
									open(this.find("name", "photo")[0].getValue());
								}, "scope": this}
							}
						}]
					}]
				}, {
					"height": 15
				}, {
					"layout": "column",
					"items": [{
						"columnWidth": .5,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "textarea",
							"readOnly": true,
							"name": "awards",
							"anchor": "100%",
							"fieldLabel": _("artrequest.applicant_awards")
						}]
					}, {
						"columnWidth": .5,
						"layout": "form",
						"labelAlign": "top",
						"items": [{
							"xtype": "textarea",
							"readOnly": true,
							"name": "contacts",
							"anchor": "100%",
							"fieldLabel": _("artrequest.applicant_contacts")
						}]
					}]
				}, {
					"layout": "column",
					"bodyStyle": "padding-top: 15px",
					"defaults": {
						"columnWidth": 1 / 3
					},
					"items": [{
						"xtype": "button",
						"itemId": "work1",
						"text": _("artrequest.work") + " 1",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work1,
										"name": this.record.work1name,
										"about": this.record.work1about,
										"framing": this.record.work1framing
									}, this);
							}, "scope": this}
						}
					}, {
						"xtype": "button",
						"itemId": "work2",
						"text": _("artrequest.work") + " 2",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work2,
										"name": this.record.work2name,
										"about": this.record.work2about,
										"framing": this.record.work2framing
									}, this);
							}, "scope": this}
						}
					}, {
						"xtype": "button",
						"itemId": "work3",
						"text": _("artrequest.work") + " 3",
						"isFormField": true,
						"hideLabel": true,
						"listeners": {
							"click": {"fn": function () {
								MODx
									.load({"xtype": "artrequest-requests-requestworkwindow"})
									.showRecord({
										"picture": this.record.work3,
										"name": this.record.work3name,
										"about": this.record.work3about,
										"framing": this.record.work3framing
									}, this);
							}, "scope": this}
						}
					}]
				}]
			});
			
			ArtRequest.RequestsRequestOrgPersonWindow.superclass.constructor.call(this, config);
		},
		
		"onRefresh": function () {
			this.setTitle(this.record.name);
			
			var toggle = { "true": "show", "false": "hide" };
			this.find("itemId", "work1")[0][toggle[!!this.record.work1]]();
			this.find("itemId", "work2")[0][toggle[!!this.record.work2]]();
			this.find("itemId", "work3")[0][toggle[!!this.record.work3]]();
		}
	}));

	Ext.reg("artrequest-requests-requestworkwindow", ArtRequest.RequestsRequestWorkWindow = Ext.extend(ArtRequest.LocalRecordWindow, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"width": 600,
				"height": 620,
				"items": [{
					"xtype": "box",
					"name": "picture",
					"height": 300,
					"autoEl": {
						"tag": "img",
						"alt": ""
					},
					"isFormField": true,
					"hideLabel": true,
					"getValue": function () {
						return this.getEl() ? this.getEl().getAttribute("src") : this.autoEl.src;
					},
					"setValue": function (src) {
						src = ArtRequest.uploads_url + src;
						
						if (this.getEl()) {
							this.getEl().set({"src": ""});
							this.getEl().set({"src": src});
						} else {
							this.autoEl.src = "";
							this.autoEl.src = src;
						}
					}
				}, {
					"xtype": "button",
					"text": _("artrequest.open_blank"),
					"isFormField": true,
					"hideLabel": true,
					"listeners": {
						"click": {"fn": function () {
							open(this.find("name", "picture")[0].getValue());
						}, "scope": this}
					}
				}, {
					"xtype": "textfield",
					"readOnly": true,
					"name": "name",
					"anchor": "100%",
					"fieldLabel": _("artrequest.work_name")
				}, {
					"xtype": "textfield",
					"readOnly": true,
					"name": "about",
					"anchor": "100%",
					"fieldLabel": _("artrequest.work_about")
				}, {
					"xtype": "textfield",
					"readOnly": true,
					"name": "framing",
					"anchor": "100%",
					"fieldLabel": _("artrequest.work_framing")
				}]
			});
			
			ArtRequest.RequestsRequestWorkWindow.superclass.constructor.call(this, config);
		},
		
		"onRefresh": function () {
			this.setTitle(this.record.name);
		}
	}));
	
	// ---------------------------------------------------------------------------------------------
	
	Ext.reg("artrequest-requestcharts", ArtRequest.RequestCharts = Ext.extend(MODx.Panel, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"url": ArtRequest.connector_url,
				"baseParams": {"action": "getChart"},
				"fields": ["category", "data"]
			});
			Ext.applyIf(config, {
				"store": new Ext.data.JsonStore({
					"url": config.url,
					"baseParams": ArtRequest.merge(config.baseParams),
					"fields": config.fields,
					"root": "results",
					"totalProperty": "total",
					"storeId": config.storeId || Ext.id(),
					"autoDestroy": true,
					"listeners": {
						"load": function () {
							Ext.getCmp("modx-content").doLayout();
						},
						"exception": function (cmp, type, action, options, response) {
							var r = Ext.decode(response.responseText);
							if (r.message) {
								MODx.msg.alert(_("error"), r.message);
							}
						}
					}
				})
			});
			Ext.applyIf(config, {
				"items": [{
					"xtype": "piechart",
					"id": Ext.id(null, "yuiswf"),
					"height": 500,
					"url": ArtRequest.assets_url + "charts.swf",
					"store": config.store,
					"categoryField": "category",
					"dataField": "data",
					"extraStyle": {
						"legend": {
							"display": "right"
						}
					},
					"legendRenderer": function (category) {
						var cmp = this, result = "";
						cmp.store.data.each(function () {
							if (this.get("category") == category) {
								result = this.get("category") + "\n" + this.get("data")
									+ " (" + (Math.round(this.get("data") / cmp.store.totalLength * 10000) / 100) + "%)";
								return false;
							}
						});
						
						return result;
					},
					"tipRenderer": function (cmp, record) {
						return record.get("category") + "\n" + record.get("data")
							+ " (" + (Math.round(record.get("data") / cmp.store.totalLength * 10000) / 100) + "%)";
					},
					"listeners": {
						"beforerender": {"fn": function (cmp) {
							cmp.store.on({
								"beforeload": {"fn": function () {
									this.getEl().mask(Ext.LoadMask.prototype.msg, Ext.LoadMask.prototype.msgCls);
									
									this.getComponent("artrequest-requestcharts-total").setText("");
								}, "scope": this},
								"load": {"fn": function () {
									var total = this.store.getTotalCount();
									if (total > 0) {
										this.getComponent("artrequest-requestcharts-total").setText("Сумма всех значений: " + total);
									}
									
									this.getEl().unmask();
								}, "scope": this},
								"exception": {"fn": function () {
									this.getEl().unmask();
								}, "scope": this},
							});
						}, "scope": this}
					}
				}, {
					"xtype": "label",
					"id": "artrequest-requestcharts-total",
					"width": "100%",
					"style": {
						"display": "block",
						"text-align": "right"
					}
				}]
			});
			
			ArtRequest.RequestCharts.superclass.constructor.call(this, config);
			
			this.store.load();
		},
		
		"filter": function (baseParams) {
			Ext.apply(this.store.baseParams, baseParams || {});
			this.store.reload();
		},
		
		"reset": function () {
			this.store.baseParams = ArtRequest.merge(this.config.baseParams);
			this.store.reload();
		}
	}));
	
	Ext.reg("artrequest-requestcharts-control", ArtRequest.RequestChartsControl = Ext.extend(MODx.Panel, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"charts": "artrequest-requestcharts",
				"layout": "form",
				"labelAlign": "top",
				"items": [{
					"xtype": "artrequest-combo",
					"mode": "local",
					"store": [
						["federalentities", _("artrequest.chart_source_federalentities")],
						["requestreason", _("artrequest.chart_source_requestreason")],
						["applicants", _("artrequest.chart_source_applicants")],
						["applicant_ages", _("artrequest.chart_source_applicant_ages")],
						["person_genders", _("artrequest.chart_source_person_genders")]
					],
					"fieldLabel": _("artrequest.chart_source"),
					"listeners": {
						"select": {"fn": function (cmp) {
							var federaldistrict = this.getComponent("artrequest-requestchartcontrols-federaldistrict");
							federaldistrict.reset();
							federaldistrict.enable();
							
							var federalsubject = this.getComponent("artrequest-requestchartcontrols-federalsubject");
							federalsubject.disable();
							federalsubject.reset();
							
							var createdon = this.getComponent("artrequest-requestchartcontrols-createdon");
							createdon.reset();
							createdon.enable();
							
							this.charts.filter({
								"source": cmp.getValue(),
								"federaldistrict": undefined,
								"federalsubject": undefined,
								"createdon_from": undefined,
								"createdon_to": undefined
							});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-combo",
					"id": "artrequest-requestchartcontrols-federaldistrict",
					"baseParams": {"action": "federaldistrict/getList"},
					"fields": ["name"],
					"valueField": "name",
					"disabled": true,
					"fieldLabel": _("artrequest.federaldistrict"),
					"listeners": {
						"select": {"fn": function (cmp) {
							var federalsubject = this.getComponent("artrequest-requestchartcontrols-federalsubject");
							federalsubject.filter({"federaldistrict": cmp.getValue()});
							federalsubject.enable();
							
							this.charts.filter({"federaldistrict": cmp.getValue(), "federalsubject": undefined});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-combo",
					"id": "artrequest-requestchartcontrols-federalsubject",
					"baseParams": {"action": "federalsubject/getList"},
					"fields": ["name"],
					"valueField": "name",
					"disabled": true,
					"fieldLabel": _("artrequest.federalsubject"),
					"listeners": {
						"select": {"fn": function (cmp) {
							this.charts.filter({"federalsubject": cmp.getValue()});
						}, "scope": this}
					}
				}, {
					"xtype": "artrequest-composite",
					"id": "artrequest-requestchartcontrols-createdon",
					"disabled": true,
					"fieldLabel": _("createdon"),
					"items": [{
						"xtype": "datefield",
						"format": MODx.config.manager_date_format,
						"flex": 1,
						"listeners": {
							"change": {"fn": function (cmp) {
								this.charts.filter({"createdon_from": cmp.getValue()});
							}, "scope": this}
						}
					}, {
						"xtype": "datefield",
						"format": MODx.config.manager_date_format,
						"flex": 1,
						"listeners": {
							"change": {"fn": function (cmp) {
								this.charts.filter({"createdon_to": cmp.getValue()});
							}, "scope": this}
						}
					}]
				}, {
					"xtype": "button",
					"text": _("artrequest.apply_filter"),
					"isFormField": true
				}, {
					"xtype": "button",
					"text": _("clear_filter"),
					"isFormField": true,
					"hideLabel": true,
					"listeners": {
						"click": {"fn": function () {
							this.items.each(function () {
								this.reset && this.reset();
								
								var config = this.config || this.initialConfig || {};
								config && config.disabled && this.disable();
							});
							
							this.charts.reset();
						}, "scope": this}
					}
				}],
				"listeners": {
					"afterrender": {"fn": function () {
						this.charts = Ext.getCmp(this.config.charts);
					}, "scope": this}
				}
			});
			
			ArtRequest.RequestChartsControl.superclass.constructor.call(this, config);
		}
	}));
	
	// ---------------------------------------------------------------------------------------------
	
	Ext.ns("ArtRequest.panel");
	
	Ext.reg("artrequest-panel-index", ArtRequest.panel.Index = Ext.extend(MODx.Panel, {
		"constructor": function (config) {
			config = config || {};
			Ext.applyIf(config, {
				"cls": "container",
				"items": [{
					"html": "<h2>" + _("artrequest") + "</h2>",
					"cls": "modx-page-header"
				}, {
					"xtype": "modx-tabs",
					"items": [{
						"title": _("artrequest.requests"),
						"cls": "main-wrapper",
						"layout": "column",
						"items": [{
							"columnWidth": 1,
							"layout": "anchor",
							"items": [{
								"xtype": "artrequest-requests",
								"id": "artrequest-requests"
							}]
						}, {
							"width": 220,
							"layout": "anchor",
							"items": [{
								"xtype": "artrequest-requests-filters",
								"requests": "artrequest-requests"
							}]
						}]
					}, {
						"title": _("statistics"),
						"cls": "main-wrapper",
						"layout": "column",
						"items": [{
							"width": 220,
							"layout": "anchor",
							"items": [{
								"xtype": "artrequest-requestcharts-control",
								"charts": "artrequest-requestcharts"
							}]
						}, {
							"columnWidth": 1,
							"layout": "anchor",
							"items": [{
								"xtype": "artrequest-requestcharts",
								"id": "artrequest-requestcharts"
							}]
						}]
					}]
				}]
			});
			
			ArtRequest.panel.Index.superclass.constructor.call(this, config);
		}
	}));
})();

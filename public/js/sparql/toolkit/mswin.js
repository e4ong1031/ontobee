/*
 *  $Id: mswin.js,v 1.9 2009/01/06 22:18:40 source Exp $
 *
 *  This file is part of the OpenLink Software Ajax Toolkit (OAT) project.
 *
 *  Copyright (C) 2005-2009 OpenLink Software
 *
 *  See LICENSE file for details.
 */
/*
	new OAT.MsWin(params)
	not to be directly called, rather accessed by Window library
	
*/

OAT.MsWin = function(optObj) {
	var self = this;

	OAT.WindowParent(this,optObj);
	this.options.statusHeight = 16;
	this.options.moveHeight = 16;
	
	OAT.Dom.applyStyle(this.div,{border:"1px solid #000",font:"menu",backgroundColor:"#fff"});
	OAT.Dom.applyStyle(this.content,{backgroundColor:"#fff",position:"relative"}); 
	OAT.Dom.applyStyle(this.move,{position:"absolute",height:self.options.moveHeight+"px",backgroundColor:"#0000a0",fontWeight:"bold",color:"#fff",border:"1px solid #000"})
	this.move.style.top = (-this.options.moveHeight-2) + "px";
	this.move.style.left = "-1px";

	this.move._Drag_movers[0][1].restrictionFunction = function(l,t) {
		return l < 0 || t < self.options.moveHeight;
	}
	
	if (this.closeBtn) {
		OAT.Dom.applyStyle(this.closeBtn,{cssFloat:"right",styleFloat:"right",fontSize:"1px",marginTop:"1px",cursor:"pointer",width:"16px",height:"14px",backgroundImage:"url("+self.options.imagePath+"MsWin_close.png)"});
	}

	if (this.maxBtn) {
		OAT.Dom.applyStyle(this.maxBtn,{cssFloat:"right",styleFloat:"right",fontSize:"1px",marginTop:"1px",cursor:"pointer",width:"16px",height:"14px",backgroundImage:"url("+self.options.imagePath+"MsWin_maximize.png)"});
	}

	if (this.minBtn) {
		OAT.Dom.applyStyle(this.minBtn,{cssFloat:"right",styleFloat:"right",fontSize:"1px",marginTop:"1px",cursor:"pointer",width:"16px",height:"14px",backgroundImage:"url("+self.options.imagePath+"MsWin_minimize.png)"});
	}

	if (this.resize) {
		OAT.Dom.applyStyle(this.resize,{width:"10px",height:"10px",fontSize:"1px",position:"absolute",right:"0px",bottom:"0px",cursor:"nw-resize",backgroundImage:"url("+self.options.imagePath+"MsWin_resize.gif)"});
	}
}

OAT.Loader.featureLoaded("mswin");

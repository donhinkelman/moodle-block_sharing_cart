/**
 *  Sharing Cart - Script
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: sharing_cart.js 764 2012-07-04 09:20:58Z malu $
 */

/**
	params = {
		str : {
			notarget       : "Target not found",
			movehere       : "Move here",
			copyhere       : "Copy here",
			edit           : "Edit",
			cancel         : "Cancel",
			backup         : "Copy to Sharing Cart".
			clipboard      : "Copying this shared item",
			confirm_delete : "Are you sure you want to delete?",
			confirm_backup : "Are you sure you want to copy to Sharing Cart?"
		}
		wwwroot  : "http://example.com",
		pix_url  : "http://example.com/theme/image.php?theme=standard&image=%s",
		instance : $this->instance->id,
		course   : $this->page->course->id,
	}
 */
function sharing_cart(params)
{
	if (!Array.prototype.map ||
		!Array.prototype.filter ||
		!Array.prototype.forEach ||
		!Array.prototype.indexOf)
	{
		Array.prototype.map = function (callback, thisObject)
		{
			var n = this.length, r = new Array(n);
			for (var i = 0; i < n; i++)
				r[i] = callback.call(thisObject, this[i], i, this);
			return r;
		}
		Array.prototype.filter = function (callback, thisObject)
		{
			var n = this.length, r = new Array();
			for (var i = 0; i < n; i++) {
				if (callback.call(thisObject, this[i], i, this))
					r.push(this[i]);
			}
			return r;
		}
		Array.prototype.forEach = function (callback, thisObject)
		{
			var n = this.length;
			for (var i = 0; i < n; i++)
				callback.call(thisObject, this[i], i, this);
		}
		Array.prototype.indexOf = function (value, startIndex)
		{
			var n = this.length;
			for (var i = startIndex || 0; i < n; i++) {
				if (this[i] == value)
					return i;
			}
			return -1;
		}
	}
	
	function Cookie(key)
	{
		var exp = new Date();
		exp.setDate(exp.getDate() + 30);
		
		this.load = function ()
		{
			var list = document.cookie.split(";");
			for (var i = 0; i < list.length; i++) {
				var m = /^\s*(.+)?\s*=\s*(.+)\s*$/.exec(list[i]);
				if (m && m[1] == key)
					return m[2];
			}
			return "";
		}
		this.save = function (value)
		{
			document.cookie = key + "=" + value + ";"
			                + "expires=" + exp.toGMTString() + ";";
		}
	}
	
	function merge(target, source)
	{
		for (var k in source)
			target[k] = source[k];
	}
	
	function clear(node)
	{
		while (node.hasChildNodes())
			node.removeChild(node.lastChild);
	}
	
	function create(tag, attrs, style)
	{
		var e = document.createElement(tag);
		if (attrs)
			merge(e, attrs);
		if (style)
			merge(e.style, style);
		return e;
	}
	
	function descendants(node, tagName, className)
	{
		return Array.prototype.filter.call(node.getElementsByTagName(tagName), function (e)
		{
			return e.className && e.className.split(/\s+/).indexOf(className) >= 0;
		});
	}
	function children(node, tagName, className)
	{
		return Array.prototype.filter.call(node.childNodes, function (e)
		{
			return e.tagName && e.tagName.toLowerCase() == tagName.toLowerCase()
				&& e.className && e.className.split(/\s+/).indexOf(className) >= 0;
		});
	}
	
	function opacity(node, value)
	{
		node.style.filter = "alpha(opacity=" + (100 * value) + ")";
		node.style.opacity = value;
		node.style.MozOpacity = value;
	}
	
	var this_url = params.wwwroot + "/course/view.php?id=" + params.course;
	
	function pix_url(name)
	{
		return params.pix_url.replace("%s", name);
	}
	function action_url(name, args)
	{
		var url = params.wwwroot + "/blocks/sharing_cart/" + name + ".php";
		if (args) {
			var q = [];
			for (var k in args)
				q.push(k + "=" + args[k]);
			url += "?" + q.join("&");
		}
		return url;
	}
	
	function icon(name, alt, css)
	{
		return create("img", { src: pix_url(name), alt: alt, className: css });
	}
	function link(title)
	{
		return create("a", { title: title, href: "javascript:void(0)" });
	}
	function hidden(name, value)
	{
		return create("input", { type: "hidden", name: name, value: value });
	}
	function option(value, title)
	{
		var e = create("option", { value: value });
		e.appendChild(document.createTextNode(title || value));
		return e;
	}
	
	
	function Clipboard(oncancel)
	{
		var board = create("div", null, { display: "none", verticalAlign: "middle" });
		var outline = descendants(document.body, "h2", "outline").shift();
		var content = descendants(document.body, "div", "course-content").shift();
		var sitetopic = descendants(document.body, "div", "sitetopic").shift();
		if (outline) {
			// course (Moodle 2.0 to 2.2)
			outline.parentNode.insertBefore(board, outline.nextSibling);
		} else if (content) {
			// course (Moodle 2.3)
			content.insertBefore(board, content.firstChild);
		} else if (sitetopic) {
			// frontpage
			sitetopic.insertBefore(board, sitetopic.firstChild);
		} else {
			// unknown...
		}
		
		this.show = function (id)
		{
			this.hide();
			
			var item = document.getElementById("sharing_cart-item-" + id);
			var text = children(item, "div", "c1").shift().firstChild.cloneNode(true);
			
			var title = document.createTextNode(params.str.clipboard + ": ");
			var cancel = link(params.str.cancel);
			cancel.onclick = oncancel;
			cancel.appendChild(icon("t/delete", cancel.title, "iconsmall"));
			[ title, text, cancel ].map(function (e) { board.appendChild(e); });
			board.style.display = "block";
		}
		this.hide = function ()
		{
			clear(board);
			board.style.display = "none";
		}
	}
	
	function Section(node, index)
	{
		var target = null;
		
		var section = descendants(node, "ul", "section").shift();
		var summary = children(node, "div", "summary").shift();
		if (section) {
			// activities exist -> append after them
			target = create("li", { className: "activity" }, { display: "none" });
			section.appendChild(target);
		} else if (summary) {
			// no activities -> insert after summary
			target = create("div", { className: "activity" }, { display: "none" });
			node.insertBefore(target, summary.nextSibling);
		} else {
			// frontpage -> insert before menus
			var menus = children(node, "div", "section_add_menus").shift();
			var ul = create("ul", { className: "section" });
			target = create("li", { className: "activity" }, { display: "none" });
			ul.appendChild(target);
			node.insertBefore(ul, menus);
		}
		descendants(node, "span", "commands").forEach(function (commands)
		{
			var cmid = /(\d+)$/.exec(commands.parentNode.parentNode.id)[1];
			var backup = link(params.str.backup);
			backup.href = action_url("backup", {
				"course" : params.course,
				"section": index,
				"module" : cmid,
				"return" : this_url
			});
			backup.onclick = function () { return confirm(params.str.confirm_backup); }
			backup.appendChild(icon("i/backup", backup.title, "iconsmall"));
			commands.appendChild(backup);
		});
		
		this.showTarget = function (item_id)
		{
			this.hideTarget();
			
			var restore = link(params.str.copyhere);
			restore.href = action_url("restore", {
				"id"      : item_id,
				"course"  : params.course,
				"section" : index,
				"return"  : this_url
			});
			restore.appendChild(icon("movehere", restore.title, "movetarget"));
			target.appendChild(restore);
			target.style.display = "block";
		}
		this.hideTarget = function ()
		{
			clear(target);
			target.style.display = "none";
		}
	}
	
	function Folder(node)
	{
		var icon = descendants(node, "img", "sharing_cart-dir").shift();
		var item = node.getElementsByTagName("ul")[0];
		var open = false;
		this.get = function () { return open; }
		this.set = function (o)
		{
			icon.src = pix_url(o ? "i/open" : "i/closed");
			item.style.display = o ? "block" : "none";
			open = o;
		}
		this.set(false);
		this.title = node.getElementsByTagName("div")[0].title;
	}
	
	
	var clipboard = null, sections = [], folders = [], actions = new function ()
	{
		function a2id(a) { return parseInt(/(\d+)$/.exec(a.parentNode.parentNode.id)[1]); }
		
		this["movedir"] = function ()
		{
			var commands = this.parentNode;
			
			var dir = commands.parentNode.parentNode.parentNode;
			var path = (dir.className.split(/\s+/).indexOf("sharing_cart-dir") >= 0)
				? dir.getElementsByTagName("div")[0].title
				: "/";
			
			var form = create("form", { action: action_url("movedir") });
			form.appendChild(hidden("id", a2id(this)));
			form.appendChild(hidden("return", this_url));
			
			var list = create("select", { name: "to" });
			list.appendChild(option("/", "/"));
			folders.forEach(function (folder, i)
			{
				list.appendChild(option(folder.title));
				if (folder.title == path)
					list.selectedIndex = 1 + i;
			});
			list.onchange = function () { this.form.submit(); }
			form.appendChild(list);
			
			var edit = link(params.str.edit);
			edit.appendChild(icon("t/edit", edit.title, "iconsmall"));
			edit.onclick = function ()
			{
				var text = create("input", { type: "text", name: "to", size: 20 });
				text.value = path;
				form.replaceChild(text, list);
				form.removeChild(edit);
				text.focus();
			}
			form.appendChild(edit);
			
			var cancel = link(params.str.cancel);
			cancel.appendChild(icon("t/delete", cancel.title, "iconsmall"));
			cancel.onclick = function ()
			{
				commands.removeChild(form);
				Array.prototype.forEach.call(commands.childNodes,
					function (c) { c.style.display = "inline"; });
			}
			form.appendChild(cancel);
			
			Array.prototype.forEach.call(commands.childNodes,
				function (c) { c.style.display = "none"; });
			commands.appendChild(form);
			
			if (folders.length == 0)
				edit.onclick();
		}
		
		this["move"] = function ()
		{
			var TARGET_CLASS = "sharing_cart-move-target";
			
			var id = a2id(this), self = this.parentNode.parentNode;
			var spacer = descendants(self.parentNode, "img", "spacer").shift();
			
			if (children(self.parentNode, "li", TARGET_CLASS).length)
				return;
			
			function target(to)
			{
				var e = create("li", { className: ["r0", TARGET_CLASS].join(" ") });
				if (spacer) {
					e.appendChild(create("img", { src: params.wwwroot + "/pix/spacer.gif",
						alt: "", width: spacer.width, height: 10, className: "spacer" }));
				}
				var a = link(params.str.movehere);
				a.href = action_url("move", { "id": id, "to": to, "return": this_url });
				a.appendChild(icon("movehere", a.title, "movetarget"));
				e.appendChild(a);
				return e;
			}
			
			var cancel = link(params.str.cancel);
			cancel.onclick = function ()
			{
				children(self.parentNode, "li", TARGET_CLASS).forEach(function (target)
				{
					target.parentNode.removeChild(target);
				});
				cancel.parentNode.removeChild(cancel);
				
				children(self.parentNode, "li", "sharing_cart-item").forEach(function (item)
				{
					children(item, "span", "commands").shift().style.display = "inline";
				});
				opacity(self, 1.0);
			}
			cancel.appendChild(icon("t/left", cancel.title, "iconsmall"));
			children(self.parentNode, "li", "sharing_cart-item").forEach(function (item)
			{
				children(item, "span", "commands").shift().style.display = "none";
			});
			opacity(self, 0.5);
			self.appendChild(cancel);
			
			var current = false;
			children(self.parentNode, "li", "sharing_cart-item").forEach(function (item)
			{
				var to = parseInt(/(\d+)$/.exec(item.id)[1]);
				if (to == id)
					current = true;
				else if (!current)
					self.parentNode.insertBefore(target(to), item);
				else
					current = false;
			});
			if (!current)
				self.parentNode.appendChild(target(0));
		}
		
		this["delete"] = function ()
		{
			if (!confirm(params.str.confirm_delete))
				return;
			location.href = action_url("delete", { "id": a2id(this), "return": this_url });
		}
		
		this["restore"] = function ()
		{
			if (sections.length == 0)
				alert(params.str.notarget);
			else {
				var id = a2id(this);
				sections.forEach(function (section) { section.showTarget(id); });
				clipboard.show(id);
			}
		}
	}
	
	
	function init()
	{
		var block = document.getElementById("inst" + params.instance);
		
		// modifies block header
		var header = document.getElementById("sharing_cart-header");
		if (header) {
			var commands = descendants(block, "div", "commands").shift();
			while (header.hasChildNodes())
				commands.appendChild(header.firstChild);
			header.style.display = "none";
		}
		
		// prepare clipboard and sections
		clipboard = new Clipboard(function ()
		{
			sections.forEach(function (section) { section.hideTarget(); });
			clipboard.hide();
		});
		var choosers = descendants(document.body, "div", "addresourcemodchooser");
		var menus = descendants(document.body, "div", "section_add_menus");
		if (choosers.length) {
			// Moodle 2.3
			choosers.forEach(function (chooser, i)
			{
				sections.push(new Section(chooser.parentNode, i));
			});
		} else if (menus.length) {
			// Moodle 2.0 to 2.2
			menus.forEach(function (menu, i)
			{
				sections.push(new Section(menu.parentNode, i));
			});
		}
		
		// prepare folders
		var cookie_dir = new Cookie("sharing_cart-dir");
		descendants(block, "li", "sharing_cart-dir").forEach(function (dir, i)
		{
			var header = dir.getElementsByTagName("div")[0];
			header.id = "sharing_cart-dir-" + i;
			header.onclick = function ()
			{
				var i = parseInt(/(\d+)$/.exec(this.id)[1]);
				folders[i].set(!folders[i].get());
				cookie_dir.save(folders.map(function (f) { return f.get() ? 1 : 0; }).join(","));
			}
			header.style.cursor = "pointer";
			folders.push(new Folder(dir));
		});
		cookie_dir.load().split(",").slice(0, folders.length).forEach(function (state, i)
		{
			folders[i].set(parseInt(state));
		});
		
		// bind item actions
		descendants(block, "li", "sharing_cart-item").forEach(function (item)
		{
			Array.prototype.forEach.call(item.getElementsByTagName("a"), function (command)
			{
				var action = /(\w+)$/.exec(command.className)[1];
				command.onclick = actions[action];
			});
		});
	}
	
	
	(function (init)
	{
		if (window.addEventListener) window.addEventListener("load", init, false);
		else if (window.attachEvent) window.attachEvent("onload", init);
		else if (typeof window.onload != "function") window.onload = init;
		else {
			var pre = window.onload;
			window.onlaod = function () { pre(); init(); };
		}
	})(init);
}

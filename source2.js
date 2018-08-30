function init(){
	console.log("init() loaded");
	renderPage();
	var images = null;
	
	//TODO remove this
	this.getImages = () => {
		return (images || []).slice();
	}
	
	function renderPage(){
		let im, sb, nm, fs, tas, tds, ti, th, fub, hb;
		if(im = document.getElementById("image")){
			const pos = ["700px", "none", "500px"];
			im.onclick = () => {
				let mHe = document.getElementById("image");
				mHe.style.maxHeight = pos[(pos.indexOf(mHe.style.maxHeight) + 1) % pos.length];
			};
		}
		delete im;
		if(sb = document.getElementById("sendButton")){
			sb.onclick = () => {
				if(validate()){
					homePageToSearch();
					hideBarError();
				}else{
					showBarError("Wrong query");
				}
			};
		}
		delete sb;
		if(nm = document.getElementsByClassName("number")){
			for(c of nm){
				c.onclick = (e) => {e
					jXHR("query=" + encodeURIComponent(query) 
						+ "&n=" + encodeURIComponent(e.target.value), e.target.value);
				};
			}
		}
		delete nm;
		if(fs = document.getElementById("fileSubmit")){
			fs.disabled = false;
			fs.onclick = () => {
				let desc = document.getElementById("desc").value;
				let file = document.getElementById("file_name").files[0];
				let tag = document.getElementById("tagName").value;
				if(tag == null || tag.trim() == "" || !checkSQLi(tag)){
					showBarError("You need to add one tag");
				}else{
					checkAndUploadImg(removeSpecialChars(desc), file, (e) => {
						let resp = JSON.parse(e.target.responseText);
						if(resp.error){
							showBarError(resp.error.msg);
						}else{
							tag = tag.trim();
							checkAndUploadAss(tag, resp.infos.img, (e) => {
								let tagResp = JSON.parse(e.target.responseText);
								if(tagResp.error){
									if(tagResp.error.code == 1){
										fs.disabled = true;
										document.getElementById("tagError").appendChild(
											generateCreateTagForm(tag, resp.infos.img, (e) => {
												let tagResp2 = JSON.parse(e.target.responseText);
												if(tagResp2.error){
													showBarError(tagResp2.error.msg);
												}else{
													document.getElementById("tagError").innerHTML = "&nbsp;";
													hideBarError();
													fileLoaderCleanUp(resp.infos.img );
												}
												fs.disabled = false;
											
										}));
									}else{
										showBarError(tagResp.error.msg);
										fs.disabled = false;
									}
								}else{
									fileLoaderCleanUp(resp.infos.img);
									fs.disabled = false;
								}
							});
						}
					});
				}
			};
		}
		delete fs;
		if(tas = document.getElementById("tagASubmit")){
			tas.onclick = () => {
				let tag = document.getElementById("tagANam").value;
				let id = document.getElementById("imgAsso").value;
				checkAndUploadAss(tag, id);
			};
		}
		delete tas;
		if(tds = document.getElementById("tagDSubmit")){
			tds.onclick = () => {
				let name = document.getElementById("tagName").value;
				let desc = document.getElementById("tagDesc").value;
				checkAndUploadTag(name, desc);
			};
		}
		delete tds;
		if(ti = document.getElementById("tagInput")){
			ti.onkeypress = (e) => {
				if(e.keyCode == 13){
					let tag = ti.value;
					let id = numId;
					checkAndUploadAss(tag, id, (e) => {
						let resp = JSON.parse(e.target.responseText);
						if(resp.error){
							if(resp.error.code == 1){
								let error = document.getElementById("error");
								error.appendChild(generateCreateTagForm(resp.infos.tag, resp.infos.img, (e) => {
									let resp = JSON.parse(e.target.responseText);
									if(resp.error){
										showBarError(resp.error.msg);
									}else{
										addTag(resp.infos.tag);
										ti = document.getElementById("tagInput");
										ti.value = "";
										ti.style.display = "none";
										hideBarError();
									}
								}));
								error.style.display = "block";
							}else{
								showBarError(resp.error.msg);
							}
						}else{
							addTag(resp.infos.tag);
							ti.value = "";
							ti.style.display = "none";
						}
					});
				}
			};
		}
		delete ti;
		if(th = document.getElementById("tagHide")){
			th.onclick = () => {
				ti = document.getElementById("tagInput");
				ti.style.display = ti.style.display == "block" ? "none" : "block";
				ti.focus();
			}
		}
		delete th;
		if(fub = document.getElementById("fileUplBut")){
			fub.onclick = () => {
				location.replace(location.pathname + "?file");
			}
		}
		delete fub;
		if(hb = document.getElementById("homeBut")){
			hb.onclick = () => {
				location.replace(location.pathname + "?home");
			}
		}
		delete hb;
	}
	
	function fileLoaderCleanUp(id){
		document.getElementById("resp").innerHTML = "Your image has been loaded "
			+ "<a href=\"" + location.pathname + "?numId=" + id + "\">here</a>";
		document.getElementById("desc").value = "";
		//TODO remove file;
		document.getElementById("tagName").value = "";
	}
	
	function generateCreateTagForm(tag, id, func){
		let inp = document.createElement("textarea");
		let sen = document.createElement("input");
		let container = document.createElement("div");
		inp.className = "tagDefine";
		inp.id = "tagDefine";
		sen.className = "tagDefineSend";
		sen.type = "button";
		sen.value = "Send";
		sen.id = "tagDefineSend";
		sen.onclick = () => {
			checkAndUploadTag(tag, removeSpecialChars(inp.value), (e) => {
				let resp = JSON.parse(e.target.responseText);
				if(resp.error){
					showBarError(resp.error.msg);
				}else{
					checkAndUploadAss(tag, id, func);
				}
			});
		}
		container.id = "tagDefineCont";
		container.innerHTML = "You need to define the tag you want to add. "
			+ "Use the form below to add a proper description<br>"
		container.appendChild(inp);
		container.appendChild(document.createElement("br"));
		container.appendChild(sen);
		return container;
	}
	
	function showBarError(error, noFade){
		let err = document.getElementById("error");
		err.innerHTML = error;
		err.style.display = "block";
		if(!noFade){
			setTimeout(() => {
				hideBarError();
			}, 6000);
		}
	}
	
	function hideBarError(){
		let err = document.getElementById("error");
		err.innerHTML = "";
		err.style.display = "none";
	}
	
	function addTag(name){
		document.getElementById("tagContainer").insertBefore(createTag(name), document.getElementById("newTag"));
	}
	
	function createTag(name){
		let newTag = document.createElement("div");
		newTag.className = "tag";
		newTag.innerHTML = "<a href=\""
			+ location.pathname
			+ "?query="
			+ name
			+ "\">"
			+ name
			+ "</a>";
		return newTag;
	}
	
	function checkAndUploadAss(tag, id, func){
		if(tag == null || tag.trim() == "" || !checkSQLi(tag)){
			showBarError("Your tag is invalid");
		}else if(id == null || id.trim() == "" || !checkSQLi(id) || id.match(/[^0-9]+/)){
			showBarError("Your image id is invalid");
		}else{
			uploadAss(tag.trim(), id.trim(), func);
		}
	}
	
	function uploadAss(tag, id, func){
		commitXHR("tagANam=" + encodeURIComponent(tag) + "&imgAsso=" + encodeURIComponent(id), func);
	}
	
	function checkAndUploadTag(name, desc, func){
		console.log(desc)
		if(name == null || name.trim() == "" || !checkSQLi(name)){
			showBarError("Your tag name is invalid");
		}else if(desc == null || desc.trim() == "" || !checkSQLi(desc)){
			showBarError("Your description is invalid");
		}else{
			uploadTag(name.trim(), desc.trim(), func);
		}
	}
	
	function uploadTag(name, desc, func){
		commitXHR("tagName=" + encodeURIComponent(name) + "&tagDesc=" + encodeURIComponent(desc), func);
	}
	
	function checkAndUploadImg(desc, file, func){
		if(desc == null || desc.trim() == "" || !checkSQLi(desc)){
			showBarError("Add a valid description");
		}else if(!file || !isImage(file.type) || file.size > 1024000*24){
			showBarError("Your file is invalid");
		}else{
			uploadFile(file, desc.trim(), func);
		}
		
	}
	
	function isImage(input){
		return input.match("image");
	}

	function commitXHR(param, func){
		let XHR = new XMLHttpRequest();
		XHR.open("POST", location.pathname, true);
		XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		XHR.onreadystatechange = (e) => {
			if(XHR.readyState == 4 && func){
				func(e);
			}
		};
		XHR.send(param);
	}

	function uploadFile(file, desc, func){
		let XHR = new XMLHttpRequest();
		let fd = new FormData();
		fd.append("file_name", file);
		fd.append("desc", desc);
		XHR.open("POST", location.pathname, true);
		XHR.onreadystatechange = (e) => {
			if(XHR.readyState == 4 && func){
				func(e);
			}
		};/*
		XHR.upload.onprogress = (e) => {
			if(e.lengthComputable){
				let percComp = e.loaded / e.total * 100;
				console.log(percComp);
			}
		}*/
		XHR.send(fd);
	}
	
	function assignButton(){
		if(document.getElementsByClassName("number")){
			for(let b of document.getElementsByClassName("number")){
				b.onclick = (e) => {
					document.getElementById("page").innerHTML = createPage(images, e.target.value);
					assignButton();
				};
			}
			delete b;
		}
	}

	function parseJson(input, page){
		let json = JSON.parse(input);
		if(json.length > 0){
			images = json;
			return createPage(json, page);
		}else{
			return getNoImage();
		}
	}
	
	function getNoImage(){
		return "<div class=\"noTag\">"
			+ "No images found for your tag.<br>(Why don't you upload some?)"
			+ "</div>";
	}
	
	function createPage(json, page){
		let result = "";
		if(json.length < 1){
			return getNoImage();
		}
		if(!(page in json)){
			page = 0;
		}
		for(im of json[page]){
			result += printOne(im);
		}
		result += printButton(json);
		return result;
	}
	
	function printButton(input){
		let result = "";
		for(let b in input){
			result += "<input type=\"button\" class=\"number\" value=\"" + b + "\"></input>";
		}
		return "<div class=\"bNContainer\">"
			+ result
			+ "</div>";
	}
	
	function printOne(input){
		return 	"<div class=\"container\">"
				+ "<div class=\"imageMiniature\">"
				+ "<img class=\"imageM\" src=\"" + input.src + "\">"
				+ "</div>"
				+ "<div class=\"info\">"
				+ "<a href=\"" + input.url + "\">"
				+ ">>" + input.num
				+ "</a>"
				+ "</div>"
				+ "</div>";
	}
	
	function checkSQLi(toValid){
		const invalid = ["\"", "\'", "--", "`"];
		for(let c of invalid){
			if(toValid.indexOf(c) > -1){
				return false;
			}
		}
		return true;
	}
	
	function removeSpecialChars(input){
		const chars = {
			"\"":"&quot;",
			"`":"&#96;",
			"'":"&#39;",
			"-":"&#45;",
			"‘":"&#8216;",
			"’":"&#8217;",
			"“":"&#8220;",
			"”":"&#8221;",
			"´":"&#180;"
		};
		for(let c in chars){
			while(input.indexOf(c) >= 0){
				input = input.replace(c, chars[c]);
			}
		}
		return input;
	}
	
	function validate(){
		let text = document.getElementById("query").value;
		return !(text == null || text.trim() == "" || !checkSQLi(text));
	}
	
	function homePageToSearch(){
		jXHR("query=" + encodeURIComponent(document.getElementById("query").value))
	}
	
	function jXHR(param, page = 0){
		let XHR = new XMLHttpRequest();
		XHR.open("POST", location.pathname, true);
		XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		XHR.onreadystatechange = () => {
			if(XHR.readyState == 4){
				document.getElementById("page").innerHTML = parseJson(XHR.responseText, page);
				assignButton();
			}
		};
		XHR.send(param);
	}
}

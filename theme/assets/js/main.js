/**
 * Partikulier — scripts front-end.
 * Zero jQuery. ES6 natif, ~3 ko minifie.
 *
 * - Menu mobile (toggle + ESC)
 * - Dropdowns clavier
 * - Dropzone photos du formulaire d'annonce
 * - Soumission AJAX du formulaire d'annonce (fetch + FormData)
 */
(function () {
	"use strict";

	/* ---------- Menu mobile ---------- */
	var toggle = document.querySelector(".pk-nav-toggle");
	var mobileMenu = document.getElementById("pk-mobile-menu");
	if (toggle && mobileMenu) {
		toggle.addEventListener("click", function () {
			var open = !toggle.classList.toggle("pk-open");
			if (open) {
				mobileMenu.removeAttribute("hidden");
			} else {
				mobileMenu.setAttribute("hidden", "");
			}
			toggle.setAttribute("aria-expanded", open ? "true" : "false");
		});
		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape" && toggle.classList.contains("pk-open")) {
				toggle.click();
				toggle.focus();
			}
		});
	}

	/* ---------- Fermeture dropdown au clic exterieur ---------- */
	document.addEventListener("click", function (e) {
		if (!e.target.closest(".pk-has-children")) {
			var open = document.querySelectorAll(".pk-has-children.pk-focus");
			for (var i = 0; i < open.length; i++) {
				open[i].classList.remove("pk-focus");
			}
		}
	});

	/* ---------- Dropzone photos ----------
	   Le parcours de depot en 3 etapes (submit-steps.js) gere lui-meme ses
	   photos. Deux gestionnaires sur le meme champ se neutralisent : celui-ci
	   vidait l'input apres chaque selection. On ne l'active donc que sur
	   l'ancien formulaire, sans etapes. */
	var dropzone = document.getElementById("pk-dropzone");
	var fileInput = document.getElementById("pk-photos");
	var preview = document.getElementById("pk-photo-preview");
	if (dropzone && fileInput && preview && !document.querySelector(".pk-steps")) {
		var MAX_FILES = 15;
		var selected = [];

		dropzone.addEventListener("click", function () {
			fileInput.click();
		});

		["dragenter", "dragover"].forEach(function (type) {
			dropzone.addEventListener(type, function (e) {
				e.preventDefault();
				dropzone.classList.add("dragover");
			});
		});
		["dragleave", "drop"].forEach(function (type) {
			dropzone.addEventListener(type, function (e) {
				e.preventDefault();
				dropzone.classList.remove("dragover");
			});
		});
		dropzone.addEventListener("drop", function (e) {
			addFiles(Array.prototype.slice.call(e.dataTransfer.files));
		});
		fileInput.addEventListener("change", function () {
			addFiles(Array.prototype.slice.call(fileInput.files));
			fileInput.value = "";
		});

		function addFiles(files) {
			files = files.filter(function (f) {
				return /^image\/(jpeg|png|webp|avif)$/.test(f.type);
			});
			var total = selected.length + files.length;
			files = files.slice(0, Math.max(0, MAX_FILES - selected.length));
			files.forEach(function (f) {
				selected.push(f);
				var li = document.createElement("li");
				var img = document.createElement("img");
				img.src = URL.createObjectURL(f);
				img.alt = f.name;
				img.loading = "lazy";
				li.appendChild(img);
				li.title = "Retirer " + f.name;
				li.style.cursor = "pointer";
				var idx = selected.length - 1;
				li.addEventListener("click", function () {
					selected[idx] = null;
					li.remove();
					syncDataTransfer();
				});
				preview.appendChild(li);
			});
			if (total > MAX_FILES) {
				setStatus(MAX_FILES + " photos maximum. Les fichiers excédentaires ont été ignorés.");
			}
			syncDataTransfer();
		}

		/**
		 * Recree le DataTransfer du champ file avec les fichiers conserves
		 * (le <input multiple> natif ne permet pas de retirer un fichier).
		 */
		function syncDataTransfer() {
			var dt = new DataTransfer();
			selected.forEach(function (f) {
				if (f) dt.items.add(f);
			});
			fileInput.files = dt.files;
		}
	}

	/* ---------- Favoris (wishlist) en localStorage ---------- */
	var wishlistButtons = document.querySelectorAll(".pk-card-wishlist");
		// Expose le branchement pour les cartes chargees en AJAX (page Favoris).
		window.pkBindWishlist = function () {
			var saved = [];
			try { saved = JSON.parse(window.localStorage.getItem("pk_wishlist") || "[]"); } catch (x) { saved = []; }

			document.querySelectorAll(".pk-card-wishlist").forEach(function (b) {
				// Etat au chargement : un favori deja enregistre s'affiche rouge.
				var currentId = b.getAttribute("data-post-id");
				if (saved.indexOf(currentId) !== -1) {
					b.classList.add("pk-wish-active");
					b.setAttribute("aria-pressed", "true");
					b.setAttribute("aria-label", "Retirer des favoris");
				} else {
					b.setAttribute("aria-pressed", "false");
				}

				if (b.dataset.pkBound) return;
				b.dataset.pkBound = "1";
				b.addEventListener("click", function (e) {
					e.preventDefault();
					e.stopPropagation();
					var id = b.getAttribute("data-post-id");
					var list = [];
					try { list = JSON.parse(window.localStorage.getItem("pk_wishlist") || "[]"); } catch (x) { list = []; }
					var i = list.indexOf(id);
					if (i === -1) {
						list.push(id);
						b.classList.add("pk-wish-active");
						b.setAttribute("aria-pressed", "true");
						b.setAttribute("aria-label", "Retirer des favoris");
					} else {
						list.splice(i, 1);
						b.classList.remove("pk-wish-active");
						b.setAttribute("aria-pressed", "false");
						b.setAttribute("aria-label", "Ajouter aux favoris");
					}
					try { window.localStorage.setItem("pk_wishlist", JSON.stringify(list)); } catch (x) {}

					// Compteur serveur anonyme, defini plus bas ; absent sur
					// certaines pages, d'ou la verification.
					if (typeof window.pkSyncFavorite === "function") {
						window.pkSyncFavorite(id, i === -1);
					}

					// Confirmation visuelle du geste.
					b.classList.remove("pk-wish-pop");
					void b.offsetWidth;
					b.classList.add("pk-wish-pop");
				});
			});
		};
		window.pkBindWishlist();
		if (wishlistButtons.length) {
			var WISH_KEY = "pk_wishlist";
			var VISITOR_KEY = "pk_favorite_visitor";
			var getWishlist = function () {
			try {
				return JSON.parse(window.localStorage.getItem(WISH_KEY) || "[]");
			} catch (e) {
				return [];
			}
		};
			var setWishlist = function (list) {
				try {
					window.localStorage.setItem(WISH_KEY, JSON.stringify(list));
				} catch (e) {
					// Espace localStorage indisponible : rien de grave.
				}
			};
			var getVisitorId = function () {
				try {
					var visitor = window.localStorage.getItem(VISITOR_KEY);
					if (!visitor) {
						visitor = window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID().replace(/-/g, "") : ("pk" + Date.now() + Math.random().toString(36).slice(2));
						window.localStorage.setItem(VISITOR_KEY, visitor);
					}
					return visitor;
				} catch (e) {
					return "";
				}
			};
			var syncFavorite = function (id, active) {
				if (!id || !pkConfig || !pkConfig.ajaxUrl || !pkConfig.nonce) return;
				var visitor = getVisitorId();
				if (!visitor || !window.fetch) return;
				var body = new URLSearchParams({
					action: "pk_sync_favorite",
					nonce: pkConfig.nonce,
					post_id: id,
					visitor_id: visitor,
					state: active ? "save" : "remove"
				});
				window.fetch(pkConfig.ajaxUrl, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" }, body: body.toString() }).catch(function () {
					// Le favori local demeure disponible si le compteur serveur est indisponible.
				});
			};
			var syncWishlistUI = function () {
			var list = getWishlist();
			document.querySelectorAll(".pk-card-wishlist").forEach(function (btn) {
				var active = list.indexOf(btn.getAttribute("data-post-id")) !== -1;
				btn.classList.toggle("pk-wish-active", active);
				btn.setAttribute("aria-pressed", active ? "true" : "false");
			});
		};
		// Un seul gestionnaire de clic existe : window.pkBindWishlist plus haut.
		// Ce bloc n'expose que le compteur serveur et l'etat initial ; ajouter
		// ici un second addEventListener annulerait le premier basculement.
		window.pkSyncFavorite = syncFavorite;
		syncWishlistUI();
	}

	/* ---------- Titre automatique d'annonce ---------- */
	var titleInput = document.getElementById("pk-title");
	var typeSelect = document.getElementById("pk-type");
	var actionSelect = document.getElementById("pk-action");
	var citySelect = document.getElementById("pk-city");
		var surfaceInput = document.getElementById("pk-surface");
		var bedroomsSelect = document.getElementById("pk-bedrooms");
		var livingRoomsSelect = document.getElementById("pk-living-rooms");
		var terraceSelect = document.getElementById("pk-terrace");
		var terraceSurfaceInput = document.getElementById("pk-terrace-surface");
		var visAVisSelect = document.getElementById("pk-vis-a-vis");
		var sunshineSelect = document.getElementById("pk-sunshine");
		var titleWasEdited = false;

	function selectedLabel(select) {
		// Le parcours en 3 etapes remplace certains <select> par des champs
		// texte a autocompletion : on ne suppose plus la presence d'options.
		if (!select) return "";
		if (!select.options || typeof select.selectedIndex !== "number") {
			return select.value ? String(select.value).trim() : "";
		}
		var option = select.options[select.selectedIndex];
		return option ? option.text.trim() : "";
	}

	function titleRoomLayout(type, bedrooms, livingRooms) {
		if ("studio" === type.toLowerCase() || "0" === bedrooms) return "";
		if ("1" === bedrooms && "1" === livingRooms) return " — 1 chambre + salon";
		if ("2" === bedrooms && "1" === livingRooms) return " — 2 chambres + salon";
		if ("3+" === bedrooms && "1" === livingRooms) return " — 3 chambres + salon ou plus";
		return "";
	}

	function generateListingTitle() {
		if (!titleInput) return;
		// Le parcours en 3 etapes redige titre et description cote serveur
		// (submit-steps.js) : on n'ecrase pas son resultat.
		if (document.querySelector(".pk-steps")) return;
		var type = selectedLabel(typeSelect) || "Appartement";
		var bedrooms = bedroomsSelect ? bedroomsSelect.value : "";
		var livingRooms = livingRoomsSelect ? livingRoomsSelect.value : "";
		var isStudio = "studio" === type.toLowerCase() || "0" === bedrooms;
		var typeLabel = isStudio ? "Studio" : type;
		var size = surfaceInput && surfaceInput.value ? " de " + surfaceInput.value + " m²" : "";
			var place = selectedLabel(citySelect) || "votre ville";
			var action = selectedLabel(actionSelect).toLowerCase();
			var transaction = action.indexOf("lou") !== -1 ? "à louer" : "à vendre";
			var visAVis = visAVisSelect ? visAVisSelect.value : "Non";
			var sunshine = sunshineSelect ? sunshineSelect.value : "";
			var primaryAdvantage = visAVis === "Oui" ? " sans vis-à-vis"
				: sunshine === "Toute la journée" ? " bien ensoleillé"
				: sunshine === "Ensoleillé le matin" ? " ensoleillé le matin"
				: sunshine === "Ensoleillé l’après-midi" ? " ensoleillé l’après-midi"
				: "";
			var terrace = terraceSelect && terraceSelect.value === "Oui"
				? " avec terrasse" + (terraceSurfaceInput && terraceSurfaceInput.value ? " de " + terraceSurfaceInput.value + " m²" : "")
				: "";
			titleInput.value = typeLabel + titleRoomLayout(type, bedrooms, livingRooms) + size + primaryAdvantage + terrace + " " + transaction + " à " + place;
		}

	if (titleInput) {
		titleInput.addEventListener("input", function () {
			titleWasEdited = true;
		});
			[typeSelect, actionSelect, citySelect, surfaceInput, bedroomsSelect, livingRoomsSelect, terraceSelect, terraceSurfaceInput, visAVisSelect, sunshineSelect].forEach(function (field) {
			if (field) field.addEventListener("change", function () {
				if (!titleWasEdited) generateListingTitle();
			});
		});
	}

	/* ---------- Soumission du formulaire d'annonce ---------- */
		var terraceSurfaceField = document.getElementById("pk-terrace-surface-field");
	function syncTerraceField() {
		if (!terraceSelect || !terraceSurfaceField || !terraceSurfaceInput) return;
		var hasTerrace = terraceSelect.value === "Oui";
		terraceSurfaceField.hidden = !hasTerrace;
		terraceSurfaceInput.required = hasTerrace;
		if (!hasTerrace) terraceSurfaceInput.value = "";
	}
	if (terraceSelect) {
		terraceSelect.addEventListener("change", syncTerraceField);
		syncTerraceField();
	}
	var form = document.getElementById("pk-submit-form");
	var submitBtn = document.getElementById("pk-submit-btn");
	var statusEl = document.getElementById("pk-form-status");
	if (form && submitBtn) {
		form.addEventListener("submit", function (e) {
			if (titleInput && !titleInput.value.trim()) generateListingTitle();
			if (!form.checkValidity()) {
				// Laisse le navigateur afficher ses messages de validation natifs.
				return;
			}
			e.preventDefault();
			submitBtn.disabled = true;
			submitBtn.textContent = (pkConfig.i18n && pkConfig.i18n.publishing) || "Publication en cours…";
			setStatus("");

			var fd = new FormData(form);

			fetch(pkConfig.ajaxUrl, { method: "POST", body: fd, credentials: "same-origin" })
				.then(function (res) {
					return res.json().then(function (data) {
						if (!res.ok) throw new Error((data && data.data && data.data.message) || ((pkConfig.i18n && pkConfig.i18n.serverError) || "Erreur serveur"));
						return data;
					});
				})
				.then(function (data) {
					var payload = data && data.data ? data.data : {};
					if ("pending_whatsapp" === payload.status && payload.whatsapp_url) {
						showWhatsAppVerification(payload);
						return;
					}
					setStatus("✔ " + (payload.message || ((pkConfig.i18n && pkConfig.i18n.saved) || "Annonce enregistrée !")));
					window.location.href = payload.url || pkConfig.homeUrl;
				})
				.catch(function (err) {
					submitBtn.disabled = false;
					submitBtn.textContent = (pkConfig.i18n && pkConfig.i18n.whatsappOpen) || "Demander la validation WhatsApp";
					setStatus("✘ " + err.message + " — " + ((pkConfig.i18n && pkConfig.i18n.retry) || "réessayez ou contactez-nous."));
				});
		});
	}

	function showWhatsAppVerification(payload) {
		if (!form) return;

		// Photos refusees par WordPress : on le dit clairement plutot que
		// de livrer une annonce sans images sans explication.
		if (payload.photo_errors && payload.photo_errors.length) {
			var warn = document.createElement("div");
			warn.className = "pk-photo-errors";
			var intro = document.createElement("p");
			intro.textContent = payload.photo_errors.length + " " + ((pkConfig.i18n && pkConfig.i18n.photoError) || "photo(s) n'ont pas pu être ajoutées :");
			warn.appendChild(intro);
			var ul = document.createElement("ul");
			payload.photo_errors.forEach(function (msg) {
				var li = document.createElement("li");
				li.textContent = msg;
				ul.appendChild(li);
			});
			warn.appendChild(ul);
			var hint = document.createElement("p");
			hint.textContent = (pkConfig.i18n && pkConfig.i18n.photoHint) || "Vous pourrez les ajouter depuis « Mes annonces » après validation.";
			warn.appendChild(hint);
			form.parentNode.insertBefore(warn, form);
		}
		form.hidden = true;
		var panel = document.createElement("section");
		panel.className = "pk-whatsapp-verification";
		panel.tabIndex = -1;

		var title = document.createElement("h2");
		title.textContent = (pkConfig.i18n && pkConfig.i18n.whatsappTitle) || "Une dernière étape : WhatsApp";
		var intro = document.createElement("p");
		intro.textContent = (pkConfig.i18n && pkConfig.i18n.whatsappIntro) || "Votre annonce est enregistrée, mais reste invisible tant que l’équipe n’a pas rapproché votre message WhatsApp.";
		var code = document.createElement("p");
		code.className = "pk-whatsapp-code";
		code.textContent = ((pkConfig.i18n && pkConfig.i18n.whatsappCode) || "Code de validation :") + " " + (payload.verification_code || "—");
		var link = document.createElement("a");
		link.className = "pk-btn pk-btn-primary";
		link.href = payload.whatsapp_url;
		link.target = "_blank";
		link.rel = "noopener";
		link.textContent = (pkConfig.i18n && pkConfig.i18n.whatsappOpen) || "Ouvrir WhatsApp et envoyer le message";
		var note = document.createElement("p");
		note.className = "pk-form-note";
		note.textContent = (pkConfig.i18n && pkConfig.i18n.whatsappNote) || "Conservez ce code. L’annonce sera publiée seulement après vérification manuelle du message par l’équipe Partikulier.";

		panel.appendChild(title);
		panel.appendChild(intro);
		panel.appendChild(code);
		panel.appendChild(link);
		panel.appendChild(note);
		form.parentNode.insertBefore(panel, form);
		panel.focus();
	}

	function setStatus(msg) {
		if (statusEl) statusEl.textContent = msg;
	}

	/* ---------- Tableau de bord proprietaire (mes annonces) ---------- */
	var manageButtons = document.querySelectorAll(".pk-manage-btn");
	if (manageButtons.length && window.pkConfig && pkConfig.manageNonce) {
		manageButtons.forEach(function (btn) {
			btn.addEventListener("click", function () {
				var action = btn.getAttribute("data-action");
				var postId = btn.getAttribute("data-post-id");
				var item = btn.closest(".pk-listing-item");
				var feedback = item ? item.querySelector(".pk-listing-feedback") : null;

				var confirmMsg = btn.getAttribute("data-confirm");
				if (confirmMsg && !window.confirm(confirmMsg)) {
					return;
				}
				if (btn.classList.contains("pk-btn-text")) {
					btn.disabled = true;
					btn.textContent = "…";
				} else {
					btn.disabled = true;
				}

				var fd = new FormData();
				fd.append("action", "pk_manage_listing");
				fd.append("nonce", pkConfig.manageNonce);
				fd.append("manage_action", action);
				fd.append("post_id", postId);

				fetch(pkConfig.ajaxUrl, { method: "POST", body: fd, credentials: "same-origin" })
					.then(function (res) {
						return res.json().then(function (data) {
							if (!res.ok) throw new Error((data && data.data && data.data.message) || ((pkConfig.i18n && pkConfig.i18n.serverError) || "Erreur serveur"));
							return data;
						});
					})
					.then(function () {
						if ("delete" === action && item) {
							item.classList.add("pk-listing-trashed");
							if (feedback) feedback.textContent = "✔ Annonce supprimée.";
							window.setTimeout(function () { window.location.reload(); }, 1200);
						} else if ("reactivate" === action) {
							window.location.reload();
						} else {
							if (feedback) feedback.textContent = "✔ " + (action === "mark_sold" ? "Annonce marquée vendue." : "Annonce marquée louée.");
							window.setTimeout(function () { window.location.reload(); }, 1200);
						}
					})
					.catch(function (err) {
						btn.disabled = false;
						if (feedback) feedback.textContent = "✘ " + err.message;
					});
			});
		});
	}
})();
/* Partage natif de la fiche annonce (Web Share API, repli presse-papiers). */
(function () {
	var btn = document.querySelector(".pk-single-share");
	if (!btn) return;
	btn.addEventListener("click", function () {
		var url = btn.getAttribute("data-url") || window.location.href;
		var title = btn.getAttribute("data-title") || document.title;
		if (navigator.share) {
			navigator.share({ title: title, url: url }).catch(function () {});
			return;
		}
		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(function () {
				var old = btn.getAttribute("aria-label");
				btn.setAttribute("aria-label", "Lien copié");
				setTimeout(function () { btn.setAttribute("aria-label", old); }, 2000);
			});
		}
	});
})();

/* Bascule grille / liste sur l'archive, memorisee entre les visites. */
(function () {
	var toggle = document.querySelector(".pk-view-toggle");
	var grid = document.querySelector(".pk-grid-cards");
	if (!toggle || !grid) return;
	var KEY = "pk_view_mode";

	function apply(mode) {
		grid.classList.toggle("pk-grid-list", mode === "list");
		toggle.querySelectorAll(".pk-view-btn").forEach(function (b) {
			var on = b.getAttribute("data-view") === mode;
			b.classList.toggle("is-active", on);
			b.setAttribute("aria-pressed", on ? "true" : "false");
		});
	}
	try { apply(localStorage.getItem(KEY) === "list" ? "list" : "grid"); } catch (e) {}

	toggle.addEventListener("click", function (e) {
		var btn = e.target.closest(".pk-view-btn");
		if (!btn) return;
		var mode = btn.getAttribute("data-view");
		apply(mode);
		try { localStorage.setItem(KEY, mode); } catch (err) {}
	});
})();

/* Sélecteur de langue : ouverture/fermeture du menu déroulant. */
(function () {
	var box = document.querySelector("[data-pk-lang]");
	if (!box) return;
	var btn = box.querySelector(".pk-lang-toggle");
	var menu = box.querySelector(".pk-lang-menu");
	if (!btn || !menu) return;

	function close() {
		menu.hidden = true;
		btn.setAttribute("aria-expanded", "false");
	}
	btn.addEventListener("click", function (e) {
		e.stopPropagation();
		var open = menu.hidden;
		menu.hidden = !open;
		btn.setAttribute("aria-expanded", open ? "true" : "false");
	});
	document.addEventListener("click", function (e) {
		if (!box.contains(e.target)) close();
	});
	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape") close();
	});
})();

/* ---------- Page Favoris ---------- */
(function () {
	"use strict";
	var grid = document.getElementById("pk-favorites-grid");
	if (!grid || typeof pkConfig === "undefined") return;

	var empty = document.getElementById("pk-favorites-empty");
	var countEl = document.getElementById("pk-fav-count");
	var ids = [];
	try { ids = JSON.parse(window.localStorage.getItem("pk_wishlist") || "[]"); } catch (e) { ids = []; }

	function showEmpty() {
		if (empty) empty.hidden = false;
		if (countEl) countEl.textContent = "Aucune annonce enregistrée pour l’instant.";
	}

	if (!ids.length) { showEmpty(); return; }

	var body = new FormData();
	body.append("action", "pk_favorites_list");
	body.append("nonce", pkConfig.nonce);
	body.append("ids", ids.join(","));

	fetch(pkConfig.ajaxUrl, { method: "POST", body: body, credentials: "same-origin" })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (!data || !data.success || !data.data.count) { showEmpty(); return; }
			grid.innerHTML = data.data.html;
			if (countEl) {
				countEl.textContent = data.data.count + (data.data.count > 1 ? " biens enregistrés" : " bien enregistré") + " depuis cet appareil.";
			}
			// Les cartes viennent d'arriver : on rebranche les coeurs.
			if (window.pkBindWishlist) window.pkBindWishlist();
		})
		.catch(showEmpty);
}());

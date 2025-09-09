/** @format */

jQuery(document).ready(function ($) {
	"use strict";

	let isGenerating = false;
	let generatedData = null;

	// Initialize
	init();

	function init() {
		bindEvents();
	}

	function bindEvents() {
		$("#aca-generate-btn").on("click", handleGenerateClick);
		$("#aca-apply-content").on("click", handleApplyContent);
		$("#aca-generate-new").on("click", handleGenerateNew);
		$("#aca-topic-input").on("keypress", function (e) {
			if (e.which === 13) {
				// Enter key
				e.preventDefault();
				handleGenerateClick();
			}
		});
	}

	function handleGenerateClick() {
		if (isGenerating) return;

		// Get the post title from the editor (Classic or Gutenberg)
		let topic = "";
		// Classic Editor
		if ($("#title").length) {
			topic = $("#title").val().trim();
		}
		// Gutenberg Editor (try multiple selectors)
		else if ($("input.editor-post-title__input").length) {
			topic = $("input.editor-post-title__input").val().trim();
		} else if ($(".editor-post-title__input").length) {
			topic = $(".editor-post-title__input").val().trim();
		} else if ($("[aria-label='Add title']").length) {
			topic = $("[aria-label='Add title']").val().trim();
		}
		// Try Gutenberg wp.data API if still empty
		if (!topic && window.wp && window.wp.data) {
			try {
				topic =
					window.wp.data
						.select("core/editor")
						.getEditedPostAttribute("title") || "";
				topic = topic.trim();
			} catch (e) {}
		}
		if (!topic) {
			alert(
				"Please enter a post title in the main title area before generating a blog post."
			);
			return;
		}
		startGeneration(topic);
	}

	function startGeneration(topic) {
		isGenerating = true;

		// Update UI
		updateButtonState(true);
		showStatusArea();
		hideResultArea();

		// Start progress simulation
		simulateProgress();

		// Make AJAX request
		const data = {
			action: "aca_generate_content",
			nonce: aca_ajax.nonce,
			topic: topic,
			post_id: getPostId(),
		};

		$.post(aca_ajax.ajax_url, data)
			.done(handleGenerationSuccess)
			.fail(handleGenerationError)
			.always(() => {
				isGenerating = false;
				updateButtonState(false);
			});
	}

	function simulateProgress() {
		const steps = [
			{ progress: 15, text: aca_ajax.strings.step1, delay: 1000 },
			{ progress: 30, text: aca_ajax.strings.step2, delay: 2000 },
			{ progress: 50, text: aca_ajax.strings.step3, delay: 3000 },
			{ progress: 65, text: aca_ajax.strings.step4, delay: 1500 },
			{ progress: 75, text: aca_ajax.strings.step5, delay: 1500 },
			{ progress: 85, text: aca_ajax.strings.step6, delay: 2000 },
			{ progress: 95, text: aca_ajax.strings.step7, delay: 2000 },
		];

		let currentStep = 0;

		function nextStep() {
			if (currentStep < steps.length && isGenerating) {
				const step = steps[currentStep];
				updateProgress(step.progress, step.text);
				currentStep++;
				setTimeout(nextStep, step.delay);
			}
		}

		// Start with first step
		updateProgress(10, aca_ajax.strings.step1);
		setTimeout(nextStep, 500);
	}

	function handleGenerationSuccess(response) {
		if (response.success) {
			updateProgress(100, aca_ajax.strings.complete);

			// Extract the correct data structure
			const data =
				response.data && response.data.data
					? response.data.data
					: response.data;
			generatedData = data;

			// Log the response for debugging
			console.log("Generation successful:", response);
			console.log("Extracted data:", data);

			setTimeout(() => {
				hideStatusArea();
				showResultArea(data);
			}, 1000);
		} else {
			console.error("Generation failed:", response);
			handleGenerationError(response);
		}
	}

	function handleGenerationError(response) {
		let errorMessage = aca_ajax.strings.error;

		if (response.data && response.data.message) {
			errorMessage = response.data.message;
		} else if (response.responseJSON && response.responseJSON.data) {
			errorMessage = response.responseJSON.data;
		}

		updateProgress(0, errorMessage, true);

		setTimeout(() => {
			hideStatusArea();
		}, 3000);
	}

	function handleApplyContent() {
		if (!generatedData || !generatedData.transient_key) {
			alert("No generated content to apply. Please generate content first.");
			return;
		}

		console.log("Applying content with data:", generatedData);

		// Update button state
		$("#aca-apply-content")
			.prop("disabled", true)
			.text(aca_ajax.strings.applying);

		const data = {
			action: "aca_apply_content",
			nonce: aca_ajax.nonce,
			transient_key: generatedData.transient_key,
			post_id: getPostId(),
		};

		console.log("Sending apply request:", data);
		console.log("Nonce value:", aca_ajax.nonce);
		console.log("Post ID:", getPostId());

		$.post(aca_ajax.ajax_url, data)
			.done(function (response) {
				console.log("Apply response:", response);
				if (response.success) {
					// Populate the editor with the generated content
					populatePostEditor(generatedData);

					// Show success message
					showSuccessMessage("Content applied successfully!");

					// Reset the meta box
					setTimeout(() => {
						handleGenerateNew();
					}, 2000);
				} else {
					console.error("Apply failed:", response);
					alert(
						"Error applying content: " + (response.data || "Unknown error")
					);
					$("#aca-apply-content").prop("disabled", false).text("Apply to Post");
				}
			})
			.fail(function (xhr, status, error) {
				console.error("Apply request failed:", xhr, status, error);
				alert("Error applying content: " + error);
				$("#aca-apply-content").prop("disabled", false).text("Apply to Post");
			});
	}

	function handleGenerateNew() {
		hideResultArea();
		$("#aca-topic-input").val("").focus();
		generatedData = null;
	}

	function updateButtonState(generating) {
		const $btn = $("#aca-generate-btn");
		const $text = $btn.find(".aca-btn-text");
		const $loading = $btn.find(".aca-btn-loading");

		if (generating) {
			$btn.prop("disabled", true);
			$text.hide();
			$loading.show();
		} else {
			$btn.prop("disabled", false);
			$text.show();
			$loading.hide();
		}
	}

	function showStatusArea() {
		$("#aca-status-area").removeClass("aca-status-hidden");
	}

	function hideStatusArea() {
		$("#aca-status-area").addClass("aca-status-hidden");
	}

	function showResultArea(data) {
		const $area = $("#aca-result-area");
		const $summary = $area.find(".aca-result-summary");

		// Build summary with safe data access
		let summary = `<strong>Title:</strong> ${data.title || "N/A"}<br>`;
		summary += `<strong>Word Count:</strong> ${data.word_count || 0} words<br>`;

		// Safely handle keywords array
		let keywordsText = "N/A";
		if (data.keywords && Array.isArray(data.keywords)) {
			keywordsText = data.keywords.join(", ");
		} else if (data.keywords && typeof data.keywords === "string") {
			keywordsText = data.keywords;
		}
		summary += `<strong>Keywords:</strong> ${keywordsText}<br>`;

		summary += `<strong>Featured Image:</strong> ${
			data.has_featured_image ? "Yes" : "No"
		}<br>`;
		summary += `<strong>Content Images:</strong> ${
			data.content_images_count || 0
		}`;

		$summary.html(summary);
		$area.removeClass("aca-result-hidden");
	}

	function hideResultArea() {
		$("#aca-result-area").addClass("aca-result-hidden");
	}

	function updateProgress(percent, text, isError = false) {
		const $fill = $(".aca-progress-fill");
		const $text = $(".aca-status-text");
		const $details = $(".aca-step-details");

		$fill.css("width", percent + "%");
		$text.text(text);

		if (isError) {
			$fill.css("background", "#dc3232");
			$text.css("color", "#dc3232");
		} else {
			$fill.css("background", "#0073aa");
			$text.css("color", "#333");
		}

		// Update details
		if (percent === 100 && !isError) {
			$details.text("Content is ready to apply to your post.");
		} else if (isError) {
			$details.text("Please check your API configuration and try again.");
		} else {
			$details.text(`Progress: ${percent}%`);
		}
	}

	function getPostId() {
		// Try multiple methods to get post ID

		// Method 1: Check if post_ID field exists (for existing posts)
		if ($("#post_ID").length && $("#post_ID").val()) {
			return parseInt($("#post_ID").val());
		}

		// Method 2: Check URL parameters
		const urlParams = new URLSearchParams(window.location.search);
		const postIdFromUrl = urlParams.get("post");
		if (postIdFromUrl) {
			return parseInt(postIdFromUrl);
		}

		// Method 3: Check global variables
		if (
			typeof pagenow !== "undefined" &&
			pagenow === "post" &&
			typeof typenow !== "undefined"
		) {
			if (window.post_id) {
				return parseInt(window.post_id);
			}
		}

		// Method 4: For new posts, return 0 (this is valid)
		console.log("No existing post ID found, treating as new post (ID: 0)");
		return 0;
	}

	/**
	 * Populate WordPress post editor with generated content
	 */
	function populatePostEditor(data) {
		try {
			console.log("Populating editor with structured content:", data);

			// 1. Set post title
			if (data.title && $("#title").length) {
				$("#title").val(data.title).trigger("input");
				console.log("Title set:", data.title);
			}

			// 2. Set post slug (if editable)
			if (data.slug && $("#post_name").length) {
				$("#post_name").val(data.slug);
			} else if (data.title) {
				// Generate slug from title
				const slug = data.title
					.toLowerCase()
					.replace(/[^a-z0-9\s-]/g, "")
					.replace(/\s+/g, "-")
					.replace(/-+/g, "-")
					.trim("-");
				if ($("#post_name").length) {
					$("#post_name").val(slug);
				}
			}

			// 3. Format and structure the content
			let structuredContent = "";

			if (data.content) {
				// Parse the content and improve structure
				structuredContent = formatContentStructure(data.content);
			}

			// 4. Set the formatted content in the editor
			if (structuredContent) {
				if (
					typeof wp !== "undefined" &&
					wp.data &&
					wp.data.select("core/editor")
				) {
					// Block editor (Gutenberg) - preferred for modern WordPress
					populateGutenbergEditor(data.title, structuredContent, data);
				} else if (typeof tinymce !== "undefined" && tinymce.get("content")) {
					// Classic editor
					tinymce.get("content").setContent(structuredContent);
				} else if ($("#content").length) {
					// Fallback to textarea
					$("#content").val(structuredContent);
				}
			}

			// 5. Set featured image if available
			if (data.featured_image && data.featured_image.id) {
				setFeaturedImage(data.featured_image.id);
			}

			// 6. Set post excerpt if available
			if (data.excerpt && $("#excerpt").length) {
				$("#excerpt").val(data.excerpt);
			}

			console.log("Post editor populated successfully with structured content");
		} catch (error) {
			console.error("Error populating editor:", error);
		}
	}

	/**
	 * Format content with proper structure
	 */
	function formatContentStructure(content) {
		// Check if content already has HTML tags (from AI generation)
		if (
			content.includes("<h2>") ||
			content.includes("<p>") ||
			content.includes("<img>")
		) {
			// Content already has proper HTML structure, return as-is
			return content.trim();
		}

		// Fallback: Only for plain text content, add basic HTML structure
		let sections = content.split(/\n\s*\n/);
		let formattedContent = "";

		sections.forEach((section, index) => {
			section = section.trim();
			if (!section) return;

			// Check if it looks like a heading
			if (
				section.length < 100 &&
				(section.includes(":") ||
					section.match(/^\d+\./) ||
					section.match(/^[A-Z][^.]*$/) ||
					section.includes("Introduction") ||
					section.includes("Conclusion") ||
					section.includes("Benefits") ||
					section.includes("Overview"))
			) {
				// Make it an H2 heading
				formattedContent += `<h2>${section.replace(/^\d+\.\s*/, "")}</h2>\n\n`;
			} else {
				// Regular paragraph
				formattedContent += `<p>${section}</p>\n\n`;
			}
		});

		return formattedContent;
	}

	/**
	 * Populate Gutenberg (Block Editor) with structured content
	 */
	function populateGutenbergEditor(title, content, data) {
		try {
			const { dispatch, select } = wp.data;

			// Set title
			dispatch("core/editor").editPost({ title: title });

			// Create blocks from the formatted content
			const blocks = [];

			// Add featured image block if available
			if (data.featured_image && data.featured_image.id) {
				blocks.push(
					wp.blocks.createBlock("core/image", {
						id: data.featured_image.id,
						url: data.featured_image.url,
						alt: data.featured_image.alt || title,
						caption: data.featured_image.caption || "",
					})
				);
			}

			// Parse HTML content into blocks
			const htmlBlocks = wp.blocks.rawHandler({ HTML: content });
			blocks.push(...htmlBlocks);

			// Add a call-to-action or conclusion if keywords are available
			if (data.keywords && data.keywords.length > 0) {
				blocks.push(
					wp.blocks.createBlock("core/paragraph", {
						content: `<em>Keywords: ${data.keywords.join(", ")}</em>`,
					})
				);
			}

			// Replace all blocks
			dispatch("core/editor").resetBlocks(blocks);

			console.log("Gutenberg editor populated with", blocks.length, "blocks");
		} catch (error) {
			console.error("Error populating Gutenberg editor:", error);
			// Fallback to simple content insertion
			wp.data.dispatch("core/editor").editPost({
				title: title,
				content: content,
			});
		}
	}

	/**
	 * Set featured image
	 */
	function setFeaturedImage(imageId) {
		try {
			if (
				typeof wp !== "undefined" &&
				wp.data &&
				wp.data.select("core/editor")
			) {
				// Gutenberg
				wp.data.dispatch("core/editor").editPost({
					featured_media: imageId,
				});
			} else {
				// Classic editor - try to set the featured image meta box
				if ($("#set-post-thumbnail").length) {
					// Trigger the featured image meta box (this requires more complex handling)
					console.log("Featured image ID set:", imageId);
				}
			}
		} catch (error) {
			console.error("Error setting featured image:", error);
		}
	}

	/**
	 * Show success message
	 */
	function showSuccessMessage(message) {
		const $notice = $(
			'<div class="notice notice-success is-dismissible"><p>' +
				message +
				"</p></div>"
		);
		$(".wrap h1").after($notice);

		// Auto-remove after 5 seconds
		setTimeout(() => {
			$notice.fadeOut();
		}, 5000);
	}
});

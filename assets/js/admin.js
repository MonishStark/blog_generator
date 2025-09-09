/** @format */

jQuery(document).ready(function ($) {
	"use strict";

	// Initialize admin functionality
	init();

	function init() {
		handleSettingsPage();
		handlePostEditor();
	}

	function handleSettingsPage() {
		// Settings page specific functionality
		if ($(".wrap h1").text().includes("AI Content Architect Settings")) {
			addApiKeyToggle();
			validateApiKeys();
		}
	}

	function handlePostEditor() {
		// Post editor specific functionality
		if ($("#post").length > 0) {
			// Add any post editor enhancements here
		}
	}

	function addApiKeyToggle() {
		// Add show/hide toggle for API key fields
		const apiKeyFields = [
			"#aca_gemini_api_key",
			"#aca_pexels_api_key",
			"#aca_unsplash_api_key",
		];

		apiKeyFields.forEach(function (fieldId) {
			const $field = $(fieldId);
			if ($field.length > 0) {
				const $toggleBtn = $(
					'<button type="button" class="button button-small aca-toggle-key">Show</button>'
				);
				$field.after($toggleBtn);

				$toggleBtn.on("click", function () {
					if ($field.attr("type") === "password") {
						$field.attr("type", "text");
						$(this).text("Hide");
					} else {
						$field.attr("type", "password");
						$(this).text("Show");
					}
				});
			}
		});
	}

	function validateApiKeys() {
		// Add API key validation
		$("#aca_gemini_api_key").on("blur", function () {
			const key = $(this).val().trim();
			if (key && key.length < 30) {
				showValidationMessage(
					$(this),
					"Google Gemini API key seems too short",
					"warning"
				);
			} else {
				hideValidationMessage($(this));
			}
		});

		$("#aca_pexels_api_key").on("blur", function () {
			const key = $(this).val().trim();
			if (key && key.length < 20) {
				showValidationMessage(
					$(this),
					"Pexels API key seems too short",
					"warning"
				);
			} else {
				hideValidationMessage($(this));
			}
		});

		$("#aca_unsplash_api_key").on("blur", function () {
			const key = $(this).val().trim();
			if (key && key.length < 20) {
				showValidationMessage(
					$(this),
					"Unsplash API key seems too short",
					"warning"
				);
			} else {
				hideValidationMessage($(this));
			}
		});
	}

	function showValidationMessage($field, message, type) {
		hideValidationMessage($field);

		const className =
			type === "error" ? "aca-validation-error" : "aca-validation-warning";
		const $message = $('<div class="' + className + '">' + message + "</div>");

		$field.after($message);

		// Add styles
		$message.css({
			color: type === "error" ? "#dc3232" : "#ffba00",
			"font-size": "12px",
			"margin-top": "5px",
			"font-style": "italic",
		});
	}

	function hideValidationMessage($field) {
		$field.siblings(".aca-validation-error, .aca-validation-warning").remove();
	}

	// Add styles for toggle buttons
	$("<style>")
		.prop("type", "text/css")
		.html(
			`
            .aca-toggle-key {
                margin-left: 10px;
                vertical-align: top;
            }
            
            .aca-validation-error,
            .aca-validation-warning {
                display: block;
                margin-top: 5px;
                font-size: 12px;
                font-style: italic;
            }
            
            .aca-validation-error {
                color: #dc3232;
            }
            
            .aca-validation-warning {
                color: #ffba00;
            }
        `
		)
		.appendTo("head");
});

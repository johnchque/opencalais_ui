# OpenCalais UI

Drupal 8 module for using the Open Calais service of Reuters that allows to analyse semantically the text of the Node and returns Tags separated by Topic that represent the content of the Node. It creates Taxonomy Terms with the returned tags and assign them to the preferred Node.

## Overview of the project's code

```
.
├── composer.json
├── config
│   ├── install                                 <- Config files for default entities needed in install.
│   └── schema
│       └── opencalais_ui.schema.yml            <- Schema files for config structure.
├── opencalais_ui.info.yml                      <- Info file for displaying the module in a Drupal installation.
├── opencalais_ui.install                       <- Install hooks.
├── opencalais_ui.links.menu.yml                <- Drupal menu element definition file.
├── opencalais_ui.links.task.yml                <- Drupal tasks definition file.
├── opencalais_ui.module                        <- Module file for defining hooks when saving a Node.
├── opencalais_ui.permissions.yml               <- Permissions files for setting to user roles.
├── opencalais_ui.routing.yml                   <- Routing file to define Drupal routes.
├── opencalais_ui.services.yml                  <- Services definition files for using the endpoints.
├── src
│   ├── CalaisService.php                       <- Service default config.
│   ├── Controller
│   │   └── OpenCalaisController.php            <- Controller for Tags form.
│   ├── Element
│   │   └── RelevanceBar.php                    <- Render element file to prepare the Twig template.
│   ├── Form
│   │   ├── GeneralSettingsForm.php             <- General settings form.
│   │   └── TagsForm.php                        <- Tags form displayed in the Tags tab of a Node entity.
│   ├── JsonProcessor.php                       <- Json processor for returning data from the service.
│   └── Tests
│       ├── OpenCalaisUiAdminTest.php           <- Admin test.
│       ├── OpenCalaisUiTagsTest.php            <- Tags test.
│       └── OpenCalaisUiTestBase.php            <- Test base file.
└── templates
    └── opencalais-ui-relevance-bar.html.twig   <- Twig template for relevance bar of Taxonomy Tags.
```

## Prerequisites

1. You have to have a Drupal instance with the ability to install modules.

   | Operating System | Tutorial                                            |
   | ---------------- | --------------------------------------------------- |
   | Mac/Linux/Windows| https://www.drupal.org/download  |

2. Enable the following modules that come with Core:
    * Path
    * Taxonomy

3. Download the module and install it.

4. Create a Reuters ID by setting up in [here](https://iameui-eagan-prod.thomsonreuters.com/iamui/UI/createUser?app_id=Bold&realm=Bold)

5. Add the Reuters ID to the config form in Drupal.

6. Select the Field where to add the Taxonomy Terms.

## Using the module

1. Create a Node that has set the field to add the Taxonomy Terms.

2. Go to the Tags tab and select "Suggest tags"

3. Select the Tags that you want to link the Node to and Save.

4. The Tags have been added to the Node.

## Contributing
Contributions are very welcome. Please go [here](https://www.drupal.org/project/issues/opencalais_ui?categories=All) and create an issue.
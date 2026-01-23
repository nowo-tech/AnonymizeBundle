# Symfony Flex Recipe

## Recipe Status

The Symfony Flex recipe for this bundle is **not published** in the official Symfony recipes repository. This means that the configuration file `config/packages/dev/nowo_anonymize.yaml` **is not automatically created** when installing the bundle from Packagist.

## Temporary Solution

While the recipe is not published, users must manually create the configuration file:

1. Create the directory if it doesn't exist:
   ```bash
   mkdir -p config/packages/dev
   ```

2. Create the file `config/packages/dev/nowo_anonymize.yaml` with the content from the template located at:
   `.symfony/recipe/nowo-tech/anonymize-bundle/0.0.1/config/packages/dev/nowo_anonymize.yaml`

## How to Publish the Recipe

For the recipe to work automatically, it must be published in the official Symfony repository:

### Option 1: Symfony Recipes Contrib (Recommended)

1. Fork the repository: https://github.com/symfony/recipes-contrib
2. Create the directory structure:
   ```
   nowo-tech/
     anonymize-bundle/
       0.0.1/
         manifest.json
         config/
           packages/
             dev/
               nowo_anonymize.yaml
         post-install.txt
   ```
3. Copy the files from `.symfony/recipe/nowo-tech/anonymize-bundle/0.0.1/`
4. Create a Pull Request in the recipes-contrib repository

### Option 2: Update the Recipe for Future Versions

When a new version of the bundle is published, you should:

1. Create a new version of the recipe (e.g., `0.0.2/`, `0.0.3/`, etc.)
2. Update the `manifest.json` if there are configuration changes
3. Publish the recipe in the official repository

## Recipe Structure

The current recipe (`0.0.1`) includes:

- **manifest.json**: Defines bundle registration and files to copy
- **config/packages/dev/nowo_anonymize.yaml**: Default configuration file
- **post-install.txt**: Message displayed after installation

## Important Notes

- The bundle **works without the configuration file** using default values
- The configuration file is only necessary to customize options
- Once the recipe is published, users installing the bundle from Packagist will automatically receive the configuration file

## References

- [Symfony Recipes Contrib](https://github.com/symfony/recipes-contrib)
- [Symfony Flex Documentation](https://symfony.com/doc/current/setup/flex_backends.html)
- [Recipes Guide](https://github.com/symfony/recipes/blob/main/README.md)

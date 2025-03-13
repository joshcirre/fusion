import Database from "./Database.js";
import path from 'path';
import fs from "fs";

export default class Cleanup {
  constructor({config}) {
    this.database = new Database(config.paths.database);
    this.config = config;
  }

  run() {
    this.database
      .prepare('select src, php_path, shim_path from components')
      .all()
      .forEach(component => {
        let src = path.join(this.config.paths.base, component.src);

        // If the source file is gone, then we need to clear out our
        // generated files and remove the entry from the database.
        if (!fs.existsSync(src)) {
          this.delete(component.php_path);
          this.delete(component.shim_path);

          this.database.delete(component.src);
        }
      });
  }

  delete(file) {
    if (file) {
      try {
        fs.unlinkSync(path.join(this.config.paths.base, file));
      } catch (e) {
        //
      }
    }
  }
}

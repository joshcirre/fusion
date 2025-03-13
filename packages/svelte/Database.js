import Database from 'better-sqlite3';

export default class ComponentDB {
  constructor(path) {
    this.db = new Database(path);
  }

  prepare(query) {
    return this.db.prepare(query);
  }

  /**
   * Ensure all columns exist for the given data object
   * @private
   * @param {Object} data - Object containing column:value pairs
   */
  ensureColumns(data) {
    const columns = this.db.prepare(`SELECT name FROM pragma_table_info('components')`).all().map(col => col.name);

    for (const key of Object.keys(data)) {
      if (key !== 'src' && !columns.includes(key)) {
        throw new Error(`Unknown column [${key}].`);
      }
    }
  }

  /**
   * Insert a new component
   * @param {string} src - The unique source identifier
   * @param {Object} data - Object containing column:value pairs
   */
  insert(src, data) {
    this.ensureColumns(data);

    const columns = ['src', ...Object.keys(data)];
    const values = ['?', ...Array(Object.keys(data).length).fill('?')];

    const stmt = this.db.prepare(`
      INSERT INTO components (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `);

    stmt.run(src, ...Object.values(data));
  }

  /**
   * Update an existing component
   * @param {string} src - The unique source identifier
   * @param {Object} data - Object containing column:value pairs
   */
  set(src, data) {
    this.ensureColumns(data);

    const setClause = Object.keys(data)
      .map(key => `${key} = ?`)
      .join(', ');

    const stmt = this.db.prepare(`
      UPDATE components
      SET ${setClause}
      WHERE src = ?
    `);

    const result = stmt.run(...Object.values(data), src);
    if (result.changes === 0) {
      throw new Error('Component not found');
    }
  }

  /**
   * Get a component by its source
   * @param {string} src - The unique source identifier
   * @returns {Object|null}
   */
  get(src) {
    const stmt = this.db.prepare('SELECT * FROM components WHERE src = ?');
    return stmt.get(src);
  }

  upsert(src, data) {
    this.ensureColumns(data);

    const columns = ['src', ...Object.keys(data)];
    const values = ['?', ...Array(Object.keys(data).length).fill('?')];
    const updates = Object.keys(data)
      .map(key => `${key} = excluded.${key}`)
      .join(', ');

    const stmt = this.db.prepare(`
      INSERT INTO components (${columns.join(', ')})
      VALUES (${values.join(', ')})
      ON CONFLICT(src) DO UPDATE SET
        ${updates}
    `);

    stmt.run(src, ...Object.values(data));
  }

  /**
   * Delete a component
   * @param {string} src - The unique source identifier
   */
  delete(src) {
    const stmt = this.db.prepare('DELETE FROM components WHERE src = ?');
    const result = stmt.run(src);
    if (result.changes === 0) {
      throw new Error('Component not found');
    }
  }

  /**
   * Close the database connection
   */
  close() {
    this.db.close();
  }
}
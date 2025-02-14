export default function logStack(ctx, next) {
  const {pipeline} = ctx;

  const table = pipeline.stack.map(s => {
    return {
      priority: s.action.priority,
      handler: s.action.handler,
    }
  })
  console.table(table);

  return next;
}
const { exec } = require("child_process");
const fs = require("fs");
const path = require("path");
const readline = require("readline");

function importJSON(filePath) {
  return new Promise((resolve, reject) => {
    try {
      const jsonArray = [];
      const fileStream = fs.createReadStream(filePath);
      const rl = readline.createInterface({
        input: fileStream,
        crlfDelay: Infinity,
      });

      rl.on("line", (line) => {
        try {
          const jsonObj = JSON.parse(line);
          if (
            typeof jsonObj === "object" &&
            jsonObj !== null &&
            Object.prototype.hasOwnProperty.call(jsonObj, "ID")
          ) {
            jsonArray.push(jsonObj);
          }
        } catch (error) {
          console.error(`Error parsing JSON from line "${line}":`, error);
        }
      });

      rl.on("close", () => {
        console.log(
          `Imported ${jsonArray.length} JSON objects from ${filePath}`
        );
        resolve(jsonArray);
      });
    } catch (error) {
      console.error("Error importing JSON file:", error);
      reject(error);
    }
  });
}

function getMappedUsers(oldUsers, newUsers) {
  const newUsersMap = new Map(
    newUsers.map((newUser) => [newUser.user_login.toLowerCase(), newUser.ID])
  );
  const userMap = new Map();

  for (const oldUser of oldUsers) {
    const matchingNewUserId = newUsersMap.get(oldUser.user_login.toLowerCase());

    if (matchingNewUserId && oldUser.ID !== matchingNewUserId) {
      userMap.set(oldUser.ID, matchingNewUserId);
    }
  }

  return userMap;
}

function fixGroupMembers(userMap) {
  let updateQuery = "";
  let dbCommand = `wp ${commandOptions.alias} db query 'DELETE FROM wp_2_bp_groups_members WHERE id > 586'`;

  runShellCommand(dbCommand);

  for (const [oldId, newId] of userMap) {
    updateQuery += `UPDATE wp_2_bp_groups_members SET user_id = ${newId} WHERE user_id = ${oldId};\n`;
  }

  dbCommand = `wp ${commandOptions.alias} db query '${updateQuery}'`;
  runShellCommand(dbCommand);
}

function fixUserPackages(userMap) {
  let updateQuery = "";
  const threshold = 10000;
  // Convert Map to an array of entries
  const entries = Array.from(userMap.entries());

  // Sort the array in descending order
  entries.sort((a, b) => {
    if (a[0] > b[0]) {
      return -1;
    } else if (a[0] < b[0]) {
      return 1;
    } else {
      return 0;
    }
  });

  for (const [oldId, newId] of entries) {
    if (oldId < newId) {
      updateQuery += `UPDATE wp_3_wcpl_user_packages SET user_id = ${newId} WHERE user_id = ${oldId};\n`;
    } else {
      updateQuery += `UPDATE wp_3_wcpl_user_packages SET user_id = ${
        threshold + newId
      } WHERE user_id = ${oldId};\n`;
    }
  }

  let dbCommand = `wp ${commandOptions.alias} db query '${updateQuery}'`;
  runShellCommand(dbCommand);
  updateQuery = `UPDATE wp_3_wcpl_user_packages SET user_id = user_id - ${threshold} WHERE user_id > ${threshold};\n`;
  dbCommand = `wp ${commandOptions.alias} db query '${updateQuery}'`;
  runShellCommand(dbCommand);
}

function renameDirectories(directoryPath, userMap) {
  console.log(`Processing ${directoryPath}`);
  const files = fs.readdirSync(directoryPath, { withFileTypes: true });

  const numericDirectories = files
    .filter((file) => {
      const numericName = parseInt(file.name, 10);
      return (
        file.isDirectory() &&
        /^\d+$/.test(file.name) &&
        userMap.has(numericName) &&
        userMap.get(numericName) !== numericName
      );
    })
    .sort((a, b) => parseInt(a.name, 10) - parseInt(b.name, 10));

  for (const dir of numericDirectories) {
    const oldName = parseInt(dir.name, 10);
    const newName = userMap.get(oldName);
    const oldPath = `${directoryPath}/${oldName}`;
    const newPath = `${directoryPath}/${newName}`;

    fs.renameSync(oldPath, newPath);
    console.log(`Renamed ${oldPath} to ${newPath}`);
  }
}

function runShellCommand(command) {
  console.log(`Executing ${command}`);
  return new Promise((resolve, reject) => {
    exec(command, (error, stdout, stderr) => {
      if (error) {
        reject(`Error: ${error}`);
        return;
      }
      if (stderr) {
        reject(`stderr: ${stderr}`);
        return;
      }

      // Resolve the promise with the parsed JSON object
      resolve(stdout);
    });
  });
}

function buildOptions() {
  const sites = ["academy", "jobs"];
  const environments = ["development", "staging", "production"];

  // Ensure we have required argument
  if (process.argv.length < 3) {
    throw new Error(`Command syntax: node fixAvatars.js [site] [environment]`);
  }

  // Ensure we have a valid site
  if (!sites.some((site) => site === process.argv[2].toLowerCase())) {
    throw new Error(`Invalid site argument. Expected 'academy' or 'jobs'.`);
  }

  // Ensure we have a valid environment, if provided
  if (
    process.argv.length >= 4 &&
    !environments.some(
      (environment) => environment === process.argv[3].toLowerCase()
    )
  ) {
    throw new Error(
      `Invalid environment argument. Expected 'staging' or 'production'.`
    );
  }

  const siteArg = process.argv[2].toLowerCase();
  const envArg =
    process.argv.length >= 4 ? process.argv[3].toLowerCase() : "development";
  const subdomain = envArg === "staging" ? "staging" : siteArg;
  const subdirectory = envArg === "staging" ? `/${siteArg}` : "";
  const tld = envArg === "development" ? "lndo.site" : "com";
  const options = {
    alias: `@${envArg}`,
    id: sites.findIndex((site) => site === siteArg) + 2,
    url: `${subdomain}.codingblackfemales.${tld}${subdirectory}`,
  };

  // Resolve the promise with the parsed JSON object
  return options;
}

let commandOptions;

try {
  commandOptions = buildOptions();
  const usersCommand = `wp ${commandOptions.alias} --url=${commandOptions.url} user list --fields=ID,user_login --orderby=ID --format=json`;

  runShellCommand(usersCommand)
    .then((result) => {
      let newUsers;

      try {
        // Assuming the output is a JSON string, parse it as an object
        newUsers = JSON.parse(result);
      } catch (e) {
        console.error(`Error parsing JSON: ${e}`);
        return;
      }
      importJSON(path.join(__dirname, `users-${commandOptions.id}.json`))
        .then((data) => {
          const oldUsers = data.map((user) => {
            return {
              ID: parseInt(user.ID, 10),
              user_login: user.user_login,
            };
          });
          const directoryPath = path.join(
            path.dirname(__dirname),
            "web/app/uploads/sites/2/avatars"
          );
          const userMap = getMappedUsers(oldUsers, newUsers);

          // Deactivate unnecessary functions
          if (userMap === undefined) {
            renameDirectories(directoryPath, userMap);
            fixGroupMembers(userMap);
          }

          fixUserPackages(userMap);
        })
        .catch((error) => {
          console.error(error);
        });
    })
    .catch((error) => {
      console.error(error);
    });
} catch (e) {
  console.error(e.message);
}
